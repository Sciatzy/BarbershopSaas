<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantLifecycleNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private TenantLifecycleNotifier $notifier) {}

    private const PLAN_MRR_PHP = [
        'starter' => 499,
        'professional' => 1299,
        'business' => 2499,
        'enterprise' => 4999,
    ];

    public function index(): View
    {
        $tenants = Tenant::query()
            ->with('latestCashierSubscription')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'plan_tier',
                'status',
                'primary_domain',
                'database_name',
                'database_provisioned_at',
                'activated_at',
                'deactivated_at',
                'owner_user_id',
                'created_at',
            ]);

        $totalMrr = $tenants->sum(
            fn (Tenant $tenant): int => self::PLAN_MRR_PHP[$tenant->plan_tier] ?? 0
        );

        return view('admin.dashboard', [
            'tenants' => $tenants,
            'totalMrr' => $totalMrr,
            'planMrrPhp' => self::PLAN_MRR_PHP,
        ]);
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill([
            'status' => 'suspended',
            'deactivated_at' => now(),
        ])->save();

        $tenant->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->update([
                'stripe_status' => 'suspended',
                'updated_at' => now(),
            ]);

        $this->notifier->notifyOwner(
            $tenant,
            'Tenant Subscription Suspended',
            "Your tenant {$tenant->name} subscription has been suspended by the platform admin."
        );

        return redirect()
            ->route('admin.dashboard')
            ->with('billing_status', "Tenant {$tenant->name} has been suspended.");
    }
}
