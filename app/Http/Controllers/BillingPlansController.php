<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingPlansController extends Controller
{
    private const PLAN_OPTIONS = [
        'starter' => [
            'label' => 'Starter',
            'amount_php' => 499,
            'checkout_route' => 'billing.checkout.starter',
            'description' => 'Best for small shops starting out.',
            'limits' => 'Up to 1 branch and 2 barbers',
        ],
        'professional' => [
            'label' => 'Professional',
            'amount_php' => 1299,
            'checkout_route' => 'billing.checkout.professional',
            'description' => 'Great for growing operations.',
            'limits' => 'Up to 1 branch and 5 barbers',
        ],
        'business' => [
            'label' => 'Business',
            'amount_php' => 2499,
            'checkout_route' => 'billing.checkout.business',
            'description' => 'Built for multi-branch teams.',
            'limits' => 'Up to 3 branches and unlimited barbers',
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'amount_php' => 4999,
            'checkout_route' => 'billing.checkout.enterprise',
            'description' => 'For large and scaling barbershop networks.',
            'limits' => 'Unlimited branches and unlimited barbers',
        ],
    ];

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $selectedTenantId = (string) $request->query('tenant', '');

        $tenants = collect();
        $tenant = null;

        if ($user->hasRole('Platform Admin')) {
            $tenants = Tenant::query()->orderBy('name')->get(['id', 'name', 'plan_tier']);

            if ($selectedTenantId !== '') {
                $tenant = $tenants->firstWhere('id', $selectedTenantId);
            }

            if (! $tenant) {
                $tenant = $tenants->first();
            }
        } else {
            $tenantId = (string) ($user->tenant_id ?? '');

            if ($tenantId !== '') {
                $tenant = Tenant::query()->find($tenantId, ['id', 'name', 'plan_tier']);
            }
        }

        $planOptions = collect(self::PLAN_OPTIONS)
            ->map(fn (array $plan, string $tier): array => [
                'tier' => $tier,
                'label' => $plan['label'],
                'amount_php' => $plan['amount_php'],
                'checkout_route' => $plan['checkout_route'],
                'description' => $plan['description'],
                'limits' => $plan['limits'],
            ])
            ->values();

        $mustContactAdminForReactivation = false;

        if ($tenant) {
            $subscription = $tenant->latestCashierSubscription;

            if ($subscription) {
                $hasOngoingSubscription = $subscription->ends_at === null || $subscription->ends_at->isFuture();
                $mustContactAdminForReactivation = in_array((string) $tenant->status, ['inactive', 'suspended'], true)
                    && $hasOngoingSubscription;
            }
        }

        return view('billing.plans', [
            'tenant' => $tenant,
            'tenants' => $tenants,
            'planOptions' => $planOptions,
            'mustContactAdminForReactivation' => $mustContactAdminForReactivation,
        ]);
    }
}
