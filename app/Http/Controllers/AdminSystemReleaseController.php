<?php

namespace App\Http\Controllers;

use App\Models\SystemRelease;
use App\Services\SystemReleaseSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSystemReleaseController extends Controller
{
    public function __construct(private SystemReleaseSyncService $syncService) {}

    public function fetchLatest(Request $request): RedirectResponse
    {
        $release = $this->syncService->syncLatest(
            actor: $request->user(),
            fetchRemote: $request->boolean('fetch_remote', true),
        );

        return redirect()
            ->route('admin.dashboard')
            ->with('billing_status', 'Latest update synced as '.($release->display_version ?: $release->version).'.');
    }

    public function publish(Request $request, SystemRelease $release): RedirectResponse
    {
        $validated = $request->validate([
            'release_notes' => ['nullable', 'string', 'max:3000'],
            'cohort_mode' => ['nullable', 'in:all_active,plan_tier,tenant_ids'],
            'cohort_plan_tier' => ['nullable', 'in:starter,professional,business,enterprise'],
            'cohort_tenant_ids' => ['nullable', 'string', 'max:5000'],
        ]);

        $cohortMode = (string) ($validated['cohort_mode'] ?? 'all_active');
        $tenantIds = collect(explode(',', (string) ($validated['cohort_tenant_ids'] ?? '')))
            ->map(fn (string $value): string => trim($value))
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        $result = $this->syncService->publishRelease(
            $release,
            $validated['release_notes'] ?? null,
            [
                'mode' => $cohortMode,
                'plan_tier' => $validated['cohort_plan_tier'] ?? null,
                'tenant_ids' => $tenantIds,
            ],
        );

        $displayVersion = (string) ($release->display_version ?: $release->version);
        $targeted = (int) ($result['targeted_tenants'] ?? 0);

        return redirect()
            ->route('admin.dashboard')
            ->with('billing_status', "Release {$displayVersion} evaluated {$targeted} tenant(s): {$result['queued_tenants']} queued, {$result['already_applied']} already applied, {$result['held']} held.");
    }
}
