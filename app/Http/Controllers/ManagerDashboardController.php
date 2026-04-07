<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
        $preferredDomain = '';
        $domainSuffix = 'localhost';
        $domainPreviewUrl = null;
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

            $domainHost = $this->resolveDomainBaseHost($request);
            $domainPortSegment = $this->resolveDomainPortSegment($request);
            $domainSuffix = $domainHost.$domainPortSegment;

            if ($tenant?->primary_domain) {
                $domainRoot = strtolower((string) $tenant->primary_domain);

                if (str_ends_with($domainRoot, '.'.$domainHost)) {
                    $preferredDomain = (string) substr($domainRoot, 0, -strlen('.'.$domainHost));
                }

                $domainUrl = str_starts_with($domainRoot, 'http://') || str_starts_with($domainRoot, 'https://')
                    ? $domainRoot
                    : $request->getScheme().'://'.$domainRoot.(str_contains($domainRoot, ':') ? '' : $domainPortSegment);

                $domainPreviewUrl = $domainUrl;
            }

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

        $availedServicesQuery = DB::table('appointments as a')
            ->leftJoin('users as customer', 'customer.id', '=', 'a.customer_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->where('a.tenant_id', $tenantId)
            ->whereNotNull('a.customer_id');

        if ($user->hasRole('Branch Manager') && ! empty($user->branch_id)) {
            $availedServicesQuery->where('a.branch_id', $user->branch_id);
        }

        $availedServices = $availedServicesQuery
            ->orderByDesc('a.booked_at')
            ->orderByDesc('a.created_at')
            ->limit(20)
            ->get([
                'a.id',
                'a.booked_at',
                'a.status',
                'a.total_price',
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
            'availedServices' => $availedServices,
            'tenant' => $tenant,
            'preferredDomain' => $preferredDomain,
            'domainSuffix' => $domainSuffix,
            'domainPreviewUrl' => $domainPreviewUrl,
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

    public function updateDomain(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('billing_error', 'No tenant found for this account.');
        }

        $validated = $request->validate([
            'preferred_domain' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
        ]);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $host = $this->resolveDomainBaseHost($request);
        $preferredDomain = strtolower((string) $validated['preferred_domain']);
        $tenant->primary_domain = $preferredDomain.'.'.$host;
        $tenant->save();

        $displayDomain = $tenant->primary_domain.$this->resolveDomainPortSegment($request);

        return redirect()->route('manager.dashboard')->with('billing_status', 'Domain updated successfully to '.$displayDomain.'.');
    }

    private function resolveDomainBaseHost(Request $request): string
    {
        $appHost = strtolower((string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST));
        $requestHost = strtolower((string) $request->getHost());
        $host = $appHost !== '' ? $appHost : $requestHost;

        if ($host === '' || in_array($host, ['127.0.0.1', '::1'], true)) {
            return 'localhost';
        }

        return $host;
    }

    private function resolveDomainPortSegment(Request $request): string
    {
        $appPort = parse_url((string) config('app.url', ''), PHP_URL_PORT);
        $port = is_int($appPort) ? $appPort : (int) $request->getPort();

        if (in_array($port, [80, 443], true)) {
            return '';
        }

        return ':'.$port;
    }
}
