<?php

namespace App\Services;

use App\Models\SystemRelease;
use App\Models\Tenant;
use App\Models\TenantSystemRelease;
use App\Models\User;
use App\Support\SystemVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SystemReleaseSyncService
{
    private const SUPPORTED_PLAN_TIERS = [
        'starter',
        'professional',
        'business',
        'enterprise',
    ];

    public function __construct(
        private SystemVersion $systemVersion,
        private TenantLifecycleNotifier $tenantLifecycleNotifier,
        private TenantSchemaMigrationService $tenantSchemaMigrationService,
    ) {}

    public function syncLatest(?User $actor = null, bool $fetchRemote = true): SystemRelease
    {
        $metadata = $this->resolveLatestMetadata($fetchRemote);

        $release = SystemRelease::query()->firstOrNew([
            'version' => (string) $metadata['version'],
        ]);

        if (! $release->exists) {
            $release->publication_status = 'draft';
        }

        $release->fill([
            'display_version' => $metadata['display_version'],
            'source' => $metadata['source'],
            'branch' => $metadata['branch'],
            'commit_hash' => $metadata['commit_hash'],
            'short_commit' => $metadata['short_commit'],
            'synced_by_user_id' => $actor?->id,
            'synced_at' => now(),
        ]);

        $release->save();

        return $release;
    }

    /**
     * @param  array{mode?:string,plan_tier?:string,tenant_ids?:array<int,string>}|null  $cohort
     * @return array{targeted_tenants:int,queued_tenants:int,new_pending:int,held:int,already_applied:int}
     */
    public function publishRelease(SystemRelease $release, ?string $releaseNotes = null, ?array $cohort = null): array
    {
        $targetTenants = $this->resolvePublishTargets($cohort);
        $targetedTenants = $targetTenants->count();

        $queuedTenants = 0;
        $newPending = 0;
        $held = 0;
        $alreadyApplied = 0;

        DB::transaction(function () use (&$queuedTenants, &$newPending, &$held, &$alreadyApplied, $release, $releaseNotes, $targetTenants): void {
            $trimmedNotes = trim((string) $releaseNotes);

            if ($trimmedNotes !== '') {
                $release->release_notes = $trimmedNotes;
            }

            $release->publication_status = 'published';
            $release->published_at = $release->published_at ?? now();
            $release->save();

            foreach ($targetTenants as $tenant) {
                $state = TenantSystemRelease::query()->firstOrNew([
                    'tenant_id' => $tenant->id,
                    'system_release_id' => $release->id,
                ]);

                if ($state->exists) {
                    if ($state->state === 'pending') {
                        $queuedTenants++;
                        continue;
                    }

                    if ($state->state === 'held') {
                        $held++;
                        continue;
                    }

                    if ($state->state === 'applied') {
                        $alreadyApplied++;
                        continue;
                    }
                }

                $isAlreadyApplied = strtolower((string) ($tenant->applied_system_version ?? '')) === strtolower((string) $release->version);

                if ($isAlreadyApplied) {
                    $state->fill([
                        'state' => 'applied',
                        'available_at' => $state->available_at ?? now(),
                        'responded_at' => $state->responded_at ?? now(),
                        'applied_at' => $state->applied_at ?? now(),
                    ]);
                    $state->save();

                    $alreadyApplied++;
                    continue;
                }

                $state->fill([
                    'state' => 'pending',
                    'hold_note' => null,
                    'available_at' => $state->available_at ?? now(),
                    'responded_at' => null,
                    'held_at' => null,
                    'applied_at' => null,
                    'applied_by_user_id' => null,
                ]);
                $state->save();

                $queuedTenants++;

                if (! $state->wasRecentlyCreated) {
                    continue;
                }

                $newPending++;

                $this->tenantLifecycleNotifier->notifyOwnerWithDetails(
                    $tenant,
                    'New System Update Available',
                    'A new platform update is available for your tenant. Open your manager dashboard to apply it now or hold it for later.',
                    [
                        'Release Version' => (string) ($release->display_version ?: $release->version),
                        'Release Status' => ucfirst((string) $release->publication_status),
                        'Published At' => (string) optional($release->published_at)->toDateTimeString(),
                        'Branch' => (string) ($release->branch ?? 'n/a'),
                        'Commit' => (string) ($release->short_commit ?? $release->commit_hash ?? 'n/a'),
                    ],
                    trim((string) ($release->release_notes ?? '')) !== '' ? (string) $release->release_notes : null,
                );
            }
        });

        return [
            'targeted_tenants' => $targetedTenants,
            'queued_tenants' => $queuedTenants,
            'new_pending' => $newPending,
            'held' => $held,
            'already_applied' => $alreadyApplied,
        ];
    }

    /**
     * @param  array{mode?:string,plan_tier?:string,tenant_ids?:array<int,string>}|null  $cohort
     * @return Collection<int, Tenant>
     */
    private function resolvePublishTargets(?array $cohort): Collection
    {
        $mode = strtolower(trim((string) ($cohort['mode'] ?? 'all_active')));

        $query = Tenant::query()->where('status', 'active');

        if ($mode === 'plan_tier') {
            $planTier = strtolower(trim((string) ($cohort['plan_tier'] ?? '')));

            if (in_array($planTier, self::SUPPORTED_PLAN_TIERS, true)) {
                $query->where('plan_tier', $planTier);
            }
        }

        if ($mode === 'tenant_ids') {
            $tenantIds = array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), (array) ($cohort['tenant_ids'] ?? [])),
                static fn (string $value): bool => $value !== '',
            ));

            if ($tenantIds === []) {
                return collect();
            }

            $query->whereIn('id', $tenantIds);
        }

        return $query->orderBy('name')->get();
    }

    public function applyTenantRelease(TenantSystemRelease $tenantRelease, User $actor): void
    {
        $tenantRelease->loadMissing(['tenant', 'systemRelease']);

        $tenant = $tenantRelease->tenant;

        if (! $tenant) {
            return;
        }

        $this->tenantSchemaMigrationService->migrateTenant($tenant);

        DB::transaction(function () use ($tenantRelease, $actor): void {
            $release = $tenantRelease->systemRelease;
            $tenant = $tenantRelease->tenant;

            if (! $release || ! $tenant) {
                return;
            }

            $tenantRelease->forceFill([
                'state' => 'applied',
                'hold_note' => null,
                'responded_at' => now(),
                'held_at' => null,
                'applied_at' => now(),
                'applied_by_user_id' => $actor->id,
            ])->save();

            $tenant->forceFill([
                'applied_system_version' => (string) $release->version,
                'applied_system_version_at' => now(),
            ])->save();
        });
    }

    public function holdTenantRelease(TenantSystemRelease $tenantRelease, ?string $holdNote = null): void
    {
        $tenantRelease->forceFill([
            'state' => 'held',
            'hold_note' => trim((string) $holdNote) !== '' ? trim((string) $holdNote) : null,
            'responded_at' => now(),
            'held_at' => now(),
        ])->save();
    }

    /**
     * @return array{version:string,display_version:string,source:string,branch:?string,commit_hash:?string,short_commit:?string}
     */
    private function resolveLatestMetadata(bool $fetchRemote): array
    {
        $resolved = $this->systemVersion->resolve();
        $remote = (string) config('app.version_sync_remote', 'origin');
        $branch = (string) config('app.version_sync_branch', 'main');

        if ($fetchRemote) {
            $this->runGitCommand(['fetch', $remote, '--tags', '--prune']);
        }

        $resolvedTag = trim((string) ($resolved['tag'] ?? ''));

        if ($resolvedTag !== '') {
            return [
                'version' => $resolvedTag,
                'display_version' => $this->displayVersion($resolvedTag),
                'source' => 'git-tag',
                'branch' => $this->stringOrNull($resolved['branch'] ?? null),
                'commit_hash' => $this->stringOrNull($resolved['commit'] ?? null),
                'short_commit' => $this->stringOrNull($resolved['short_commit'] ?? null),
            ];
        }

        $remoteRef = $remote.'/'.$branch;
        $remoteCommit = $this->runGitCommand(['rev-parse', $remoteRef]);

        if (is_string($remoteCommit) && $remoteCommit !== '') {
            $shortCommit = substr($remoteCommit, 0, 7);
            $remoteTag = $this->runGitCommand(['describe', '--tags', '--abbrev=0', $remoteRef]);
            $version = is_string($remoteTag) && $remoteTag !== '' ? $remoteTag : 'dev-'.$shortCommit;

            return [
                'version' => $version,
                'display_version' => $this->displayVersion($version),
                'source' => 'git-remote',
                'branch' => $branch,
                'commit_hash' => $remoteCommit,
                'short_commit' => $shortCommit,
            ];
        }

        $version = (string) ($resolved['version'] ?? 'dev');

        return [
            'version' => $version,
            'display_version' => $this->displayVersion($version),
            'source' => (string) ($resolved['source'] ?? 'system-version'),
            'branch' => $this->stringOrNull($resolved['branch'] ?? null),
            'commit_hash' => $this->stringOrNull($resolved['commit'] ?? null),
            'short_commit' => $this->stringOrNull($resolved['short_commit'] ?? null),
        ];
    }

    private function displayVersion(string $version): string
    {
        return str_starts_with(strtolower($version), 'v') ? $version : 'v'.$version;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function runGitCommand(array $arguments): ?string
    {
        if (! $this->canRunShellCommands()) {
            return null;
        }

        $base = base_path();
        $command = 'git -C '.escapeshellarg($base).' '.implode(' ', array_map('escapeshellarg', $arguments)).' 2>/dev/null';

        $rawOutput = shell_exec($command);

        if (! is_string($rawOutput)) {
            return null;
        }

        $trimmed = trim($rawOutput);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function canRunShellCommands(): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('shell_exec', $disabled, true);
    }
}
