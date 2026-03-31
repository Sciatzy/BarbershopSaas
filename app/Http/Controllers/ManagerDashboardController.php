<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
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

    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');
        $canManageBilling = $user->hasRole('Barbershop Admin');

        $tenant = null;
        $subscription = null;
        $hasActivePlan = false;
        $mustContactAdminForReactivation = false;
        $canRecordWalkIns = $user->hasRole('Barbershop Admin');
        $planOptions = collect(self::PLAN_OPTIONS)->map(
            fn (array $plan, string $tier): array => [
                'tier' => $tier,
                'label' => $plan['label'],
                'amount_php' => $plan['amount_php'],
                'checkout_route' => $plan['checkout_route'],
                'description' => $plan['description'],
                'limits' => $plan['limits'],
            ]
        )->values();

        if ($tenantId !== '') {
            $tenant = Tenant::query()
                ->with('latestCashierSubscription')
                ->find($tenantId);

            $subscription = $tenant?->latestCashierSubscription;
            $hasActivePlan = $tenant?->hasActivePlan() ?? false;

            if ($tenant && $subscription) {
                $hasOngoingSubscription = $subscription->ends_at === null || $subscription->ends_at->isFuture();
                $mustContactAdminForReactivation = in_array((string) $tenant->status, ['inactive', 'suspended'], true)
                    && $hasOngoingSubscription;
            }
        }

        if ($tenantId === '') {
            return view('manager.dashboard', [
                'appointments' => collect(),
                'barberPoints' => collect(),
                'services' => collect(),
                'tenant' => $tenant,
                'subscription' => $subscription,
                'hasActivePlan' => $hasActivePlan,
                'mustContactAdminForReactivation' => $mustContactAdminForReactivation,
                'canManageBilling' => $canManageBilling,
                'canRecordWalkIns' => $canRecordWalkIns,
                'planOptions' => $planOptions,
                'barbersForWalkIns' => collect(),
                'branchesForWalkIns' => collect(),
            ]);
        }

        $appointmentsQuery = DB::table('appointments as a')
            ->leftJoin('branches as b', 'b.id', '=', 'a.branch_id')
            ->leftJoin('users as barber', 'barber.id', '=', 'a.barber_id')
            ->leftJoin('users as customer', 'customer.id', '=', 'a.customer_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->where('a.tenant_id', $tenantId);

        if ($user->hasRole('Branch Manager') && ! empty($user->branch_id)) {
            $appointmentsQuery->where('a.branch_id', $user->branch_id);
        }

        $appointments = $appointmentsQuery
            ->orderByDesc('a.appointment_datetime')
            ->limit(20)
            ->get([
                'a.id',
                'a.appointment_datetime',
                'a.status',
                'b.name as branch_name',
                'barber.name as barber_name',
                'customer.name as customer_name',
                's.name as service_name',
            ]);

        $barberPointsQuery = DB::table('point_transactions as pt')
            ->join('users as u', 'u.id', '=', 'pt.barber_id')
            ->where('pt.tenant_id', $tenantId);

        if ($user->hasRole('Branch Manager') && ! empty($user->branch_id)) {
            $barberPointsQuery->where('u.branch_id', $user->branch_id);
        }

        $barberPoints = $barberPointsQuery
            ->groupBy('pt.barber_id', 'u.name')
            ->orderByDesc('total_points')
            ->get([
                'pt.barber_id',
                'u.name as barber_name',
                DB::raw('SUM(pt.points_awarded) as total_points'),
            ]);

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'price', 'duration_minutes']);

        $barbersForWalkIns = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Barber')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $branchesForWalkIns = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('manager.dashboard', [
            'appointments' => $appointments,
            'barberPoints' => $barberPoints,
            'services' => $services,
            'tenant' => $tenant,
            'subscription' => $subscription,
            'hasActivePlan' => $hasActivePlan,
            'mustContactAdminForReactivation' => $mustContactAdminForReactivation,
            'canManageBilling' => $canManageBilling,
            'canRecordWalkIns' => $canRecordWalkIns,
            'planOptions' => $planOptions,
            'barbersForWalkIns' => $barbersForWalkIns,
            'branchesForWalkIns' => $branchesForWalkIns,
        ]);
    }
}
