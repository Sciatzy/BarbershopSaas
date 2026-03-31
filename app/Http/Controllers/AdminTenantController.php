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
use Illuminate\Validation\Rules\Password;
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
            'owner_password' => ['required', Password::defaults()],
            'plan_tier' => ['required', 'in:starter,professional,business,enterprise'],
            'primary_domain' => ['nullable', 'string', 'max:255', 'unique:tenants,primary_domain'],
            'database_name' => ['nullable', 'string', 'max:255', 'unique:tenants,database_name'],
            'auto_activate' => ['nullable', 'boolean'],
            'auto_provision_db' => ['nullable', 'boolean'],
        ]);

        [$tenant, $owner] = DB::transaction(function () use ($validated): array {
            $tenant = Tenant::query()->create([
                'name' => $validated['name'],
                'plan_tier' => $validated['plan_tier'],
                'status' => ! empty($validated['auto_activate']) ? 'active' : 'pending',
                'primary_domain' => $validated['primary_domain'] ?? null,
                'database_name' => $validated['database_name'] ?? null,
                'activated_at' => ! empty($validated['auto_activate']) ? now() : null,
                'deactivated_at' => null,
            ]);

            $owner = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
            ]);

            Role::findOrCreate('Barbershop Admin', 'web');
            $owner->assignRole('Barbershop Admin');

            $tenant->forceFill(['owner_user_id' => $owner->id])->save();

            return [$tenant, $owner];
        });

        $message = 'Tenant created successfully.';

        if (! empty($validated['auto_provision_db'])) {
            $result = $this->provisioning->provisionDatabase($tenant);
            $message .= ' '.$result['message'];
        }

        $this->notifier->notifyOwner(
            $tenant,
            'Your tenant account is ready',
            "Hi {$owner->name}, your tenant {$tenant->name} has been created. Plan: {$tenant->plan_tier}."
        );

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

        $wasActive = $tenant->status === 'active';
        $previousStatus = (string) $tenant->status;
        $previousTier = (string) $tenant->plan_tier;

        $tenant->forceFill([
            'name' => $validated['name'],
            'plan_tier' => $validated['plan_tier'],
            'primary_domain' => $validated['primary_domain'] ?? null,
            'database_name' => $validated['database_name'] ?? null,
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
            $this->notifier->notifyOwner(
                $tenant,
                'Tenant Activated',
                "Your tenant {$tenant->name} has been activated."
            );
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
}
