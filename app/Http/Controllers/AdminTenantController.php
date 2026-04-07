<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLifecycleNotifier;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AdminTenantController extends Controller
{
    public function __construct(
        private TenantProvisioningService $provisioning,
        private TenantLifecycleNotifier $notifier,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'plan_tier' => ['required', 'in:starter,professional,business,enterprise'],
            'primary_domain' => ['nullable', 'string', 'max:255', 'unique:tenants,primary_domain'],
            'database_name' => ['nullable', 'string', 'max:255', 'unique:tenants,database_name'],
        ]);

        $generatedPassword = Str::password(16);
        $preferredDomain = $this->normalizeDomain((string) ($validated['primary_domain'] ?? ''));
        $preferredDatabase = $this->normalizeDatabaseName((string) ($validated['database_name'] ?? ''));
        $assignedDomain = $preferredDomain !== '' ? $preferredDomain : null;

        [$tenant, $owner] = DB::transaction(function () use ($validated, $generatedPassword, $assignedDomain, $preferredDatabase): array {
            $tenant = Tenant::query()->create([
                'name' => $validated['name'],
                'plan_tier' => $validated['plan_tier'],
                'status' => 'active',
                'primary_domain' => $assignedDomain,
                'database_name' => $preferredDatabase !== '' ? $preferredDatabase : null,
                'activated_at' => now(),
                'deactivated_at' => null,
            ]);

            $owner = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($generatedPassword),
                'email_verified_at' => now(),
            ]);

            Role::findOrCreate('Barbershop Admin', 'web');
            $owner->assignRole('Barbershop Admin');

            $tenant->forceFill(['owner_user_id' => $owner->id])->save();

            return [$tenant, $owner];
        });

        $assignedDomain = $this->provisioning->ensureDomain($tenant);
        $result = $this->provisioning->provisionDatabase($tenant);

        $systemUrl = $this->provisioning->tenantUrl($assignedDomain);
        $loginUrl = $this->provisioning->tenantUrl($assignedDomain, '/login');
        $managerUrl = $this->provisioning->tenantUrl($assignedDomain, '/manager');

        $this->notifier->notifyUserWithDetails(
            $owner,
            'Your Manager Account Credentials',
            "Hi {$owner->name}, your manager account for {$tenant->name} has been created and activated.",
            [
                'Login Email' => $owner->email,
                'Temporary Password' => $generatedPassword,
                'Login URL' => $loginUrl,
                'Assigned Domain' => $assignedDomain,
                'System URL' => $systemUrl,
                'Manager Dashboard URL' => $managerUrl,
                'Database Name' => (string) $result['database_name'],
                'Plan Tier' => ucfirst((string) $tenant->plan_tier),
            ],
            'Please sign in and change your password immediately.'
        );

        $message = 'Tenant created successfully. '.$result['message'];

        return redirect()->route('admin.dashboard')->with('billing_status', $message);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan_tier' => ['required', 'in:starter,professional,business,enterprise'],
            'primary_domain' => ['nullable', 'string', 'max:255', 'unique:tenants,primary_domain,'.$tenant->id.',id'],
            'database_name' => ['nullable', 'string', 'max:255', 'unique:tenants,database_name,'.$tenant->id.',id'],
            'status' => ['required', 'in:pending,active,inactive,suspended'],
        ]);

        $normalizedDomain = $this->normalizeDomain((string) ($validated['primary_domain'] ?? ''));
        $normalizedDatabase = $this->normalizeDatabaseName((string) ($validated['database_name'] ?? ''));

        $wasActive = $tenant->status === 'active';
        $previousStatus = (string) $tenant->status;
        $previousTier = (string) $tenant->plan_tier;

        $tenant->forceFill([
            'name' => $validated['name'],
            'plan_tier' => $validated['plan_tier'],
            'primary_domain' => $normalizedDomain !== '' ? $normalizedDomain : null,
            'database_name' => $normalizedDatabase !== '' ? $normalizedDatabase : null,
            'status' => $validated['status'],
            'activated_at' => $validated['status'] === 'active' ? ($tenant->activated_at ?? now()) : $tenant->activated_at,
            'deactivated_at' => in_array($validated['status'], ['inactive', 'suspended'], true) ? now() : null,
        ])->save();

        if (in_array($previousStatus, ['inactive', 'suspended'], true) && $tenant->status === 'active') {
            $reactivated = $tenant->subscriptions()
                ->where('stripe_status', 'suspended')
                ->update([
                    'stripe_status' => 'active',
                    'updated_at' => now(),
                ]);

            // Backward compatibility for tenants suspended before reversible status was introduced.
            if ($reactivated === 0) {
                $latestCanceled = $tenant->subscriptions()
                    ->where('stripe_status', 'canceled')
                    ->latest('updated_at')
                    ->first();

                if ($latestCanceled !== null) {
                    $tenant->subscriptions()
                        ->where('id', $latestCanceled->id)
                        ->update([
                            'stripe_status' => 'active',
                            'ends_at' => $latestCanceled->ends_at && $latestCanceled->ends_at->isFuture()
                                ? $latestCanceled->ends_at
                                : now()->addDays(30),
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        if ($previousStatus !== $tenant->status && in_array($tenant->status, ['inactive', 'suspended'], true)) {
            $tenant->subscriptions()
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->update([
                    'stripe_status' => 'suspended',
                    'updated_at' => now(),
                ]);
        }

        if (! $wasActive && $tenant->status === 'active') {
            $assignedDomain = $this->provisioning->ensureDomain($tenant);

            $provisioningResult = null;

            if ($tenant->database_provisioned_at === null) {
                $provisioningResult = $this->provisioning->provisionDatabase($tenant);
            }

            if ($provisioningResult !== null) {
                $tenant->refresh();
                $assignedDomain = $this->provisioning->ensureDomain($tenant);
                $loginUrl = $this->provisioning->tenantUrl($assignedDomain, '/login');
                $managerUrl = $this->provisioning->tenantUrl($assignedDomain, '/manager');
                $systemUrl = $this->provisioning->tenantUrl($assignedDomain);

                $subject = $provisioningResult['ok']
                    ? 'Tenant Activated and Provisioned'
                    : 'Tenant Activated - Provisioning Requires Follow-up';

                $intro = $provisioningResult['ok']
                    ? "Your tenant {$tenant->name} has been activated and environment setup is complete."
                    : "Your tenant {$tenant->name} has been activated, but database provisioning needs manual follow-up.";

                $this->notifier->notifyOwnerWithDetails(
                    $tenant,
                    $subject,
                    $intro,
                    [
                        'Tenant Name' => (string) $tenant->name,
                        'Current Status' => ucfirst((string) $tenant->status),
                        'Plan Tier' => ucfirst((string) $tenant->plan_tier),
                        'Assigned Domain' => $assignedDomain,
                        'System URL' => $systemUrl,
                        'Login URL' => $loginUrl,
                        'Manager Dashboard URL' => $managerUrl,
                        'Database Name' => (string) ($provisioningResult['database_name'] ?? $tenant->database_name ?? 'n/a'),
                        'Provisioning Result' => (string) $provisioningResult['message'],
                    ],
                    $provisioningResult['ok']
                        ? 'You can proceed with normal onboarding and operations.'
                        : 'Please contact platform support so provisioning can be completed safely.'
                );
            } else {
                $this->notifier->notifyOwner(
                    $tenant,
                    'Tenant Activated',
                    "Your tenant {$tenant->name} has been activated."
                );
            }
        }

        if ($previousTier !== (string) $tenant->plan_tier) {
            $this->notifier->notifyOwner(
                $tenant,
                'Plan Updated',
                "Your tenant {$tenant->name} plan was updated to {$tenant->plan_tier}."
            );
        }

        if ($previousStatus !== $tenant->status && $tenant->status === 'suspended') {
            $this->notifier->notifyOwner(
                $tenant,
                'Tenant Subscription Suspended',
                "Your tenant {$tenant->name} subscription has been suspended by the platform admin."
            );
        }

        if ($previousStatus !== $tenant->status && $tenant->status === 'inactive') {
            $this->notifier->notifyOwner(
                $tenant,
                'Tenant Deactivated',
                "Your tenant {$tenant->name} is currently inactive. Please contact platform support."
            );
        }

        return redirect()->route('admin.dashboard')->with('billing_status', 'Tenant updated successfully.');
    }

    public function provisionDatabase(Tenant $tenant): RedirectResponse
    {
        $result = $this->provisioning->provisionDatabase($tenant);

        if ($result['ok']) {
            $this->notifier->notifyOwner(
                $tenant,
                'Tenant Database Ready',
                "Your tenant {$tenant->name} database has been provisioned successfully."
            );
        } else {
            $this->notifier->notifyOwner(
                $tenant,
                'Tenant Database Provisioning Failed',
                "We could not automatically provision the database for tenant {$tenant->name}. Please contact support."
            );
        }

        return redirect()->route('admin.dashboard')->with(
            $result['ok'] ? 'billing_status' : 'billing_error',
            $result['message']
        );
    }

    public function resendCredentials(Tenant $tenant): RedirectResponse
    {
        $owner = $this->resolveOwnerForCredentials($tenant);

        if (! $owner || trim((string) $owner->email) === '') {
            return redirect()->route('admin.dashboard')->with('billing_error', 'No tenant owner with an email was found.');
        }

        $temporaryPassword = Str::password(16);

        $owner->forceFill([
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => $owner->email_verified_at ?? now(),
        ])->save();

        $assignedDomain = $this->provisioning->ensureDomain($tenant);
        $systemUrl = $this->provisioning->tenantUrl($assignedDomain);
        $loginUrl = $this->provisioning->tenantUrl($assignedDomain, '/login');
        $managerUrl = $this->provisioning->tenantUrl($assignedDomain, '/manager');

        $this->notifier->notifyUserWithDetails(
            $owner,
            'Manager Credentials Reset',
            "Hi {$owner->name}, your manager credentials for {$tenant->name} were reset by platform admin.",
            [
                'Login Email' => (string) $owner->email,
                'Temporary Password' => $temporaryPassword,
                'Assigned Domain' => $assignedDomain,
                'System URL' => $systemUrl,
                'Login URL' => $loginUrl,
                'Manager Dashboard URL' => $managerUrl,
                'Plan Tier' => ucfirst((string) $tenant->plan_tier),
            ],
            'Please sign in and change your password immediately.'
        );

        return redirect()->route('admin.dashboard')->with('billing_status', 'Tenant credentials were regenerated and emailed successfully.');
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if ($domain === '') {
            return '';
        }

        if (str_starts_with($domain, 'http://') || str_starts_with($domain, 'https://')) {
            $parsedHost = (string) parse_url($domain, PHP_URL_HOST);
            $domain = $parsedHost !== '' ? $parsedHost : $domain;
        }

        // Allow admins to input only a subdomain label (e.g. "myshop").
        // We normalize it into a full tenant domain using the platform base host.
        if ($domain !== '' && ! str_contains($domain, '.')) {
            $domain = $domain.'.'.$this->platformHost();
        }

        return trim($domain, " \t\n\r\0\x0B/");
    }

    private function normalizeDatabaseName(string $databaseName): string
    {
        return Str::of($databaseName)
            ->lower()
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->trim('_')
            ->value();
    }

    private function platformHost(): string
    {
        $host = strtolower((string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST));

        if ($host === '' || in_array($host, ['127.0.0.1', '::1'], true)) {
            return 'localhost';
        }

        return $host;
    }

    private function resolveOwnerForCredentials(Tenant $tenant): ?User
    {
        $ownerFromMapping = null;

        if (! empty($tenant->owner_user_id)) {
            $owner = User::withoutGlobalScopes()->find($tenant->owner_user_id);
            $ownerFromMapping = $owner;

            if (
                $owner
                && trim((string) $owner->email) !== ''
                && ((string) ($owner->tenant_id ?? '') === (string) $tenant->id || empty($owner->tenant_id))
            ) {
                if (empty($owner->tenant_id)) {
                    $owner->forceFill(['tenant_id' => $tenant->id])->save();
                }

                return $owner;
            }
        }

        $roleBasedOwner = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->role('Barbershop Admin')
            ->orderBy('id')
            ->first();

        if ($roleBasedOwner) {
            if (! empty($tenant->owner_user_id) && (int) $tenant->owner_user_id !== (int) $roleBasedOwner->id) {
                $tenant->forceFill(['owner_user_id' => $roleBasedOwner->id])->save();
            }

            return $roleBasedOwner;
        }

        // Last-resort recovery for corrupted owner mapping records.
        if ($ownerFromMapping && trim((string) $ownerFromMapping->email) !== '') {
            $ownerFromMapping->forceFill(['tenant_id' => $tenant->id])->save();
            $tenant->forceFill(['owner_user_id' => $ownerFromMapping->id])->save();

            return $ownerFromMapping;
        }

        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->first();
    }
}
