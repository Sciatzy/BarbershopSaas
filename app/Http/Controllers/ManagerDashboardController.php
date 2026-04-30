<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantSystemRelease;
use App\Models\Branch;
use App\Models\User;
use App\Services\TenantLimitValidator;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

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

    private const DASHBOARD_ACCESS_DEFINITIONS = [
        'branch_manager' => [
            'label' => 'Branch Manager',
            'description' => 'Control operational tools available to branch managers.',
            'features' => [
                'manage_services' => [
                    'label' => 'Manage Services & Pricing',
                    'description' => 'Access service archive/restore and service pricing updates.',
                ],
                'manage_queue' => [
                    'label' => 'Use Queue Dashboard',
                    'description' => 'View queue and move bookings through queue statuses.',
                ],
                'manage_barbers' => [
                    'label' => 'Manage barbers',
                    'description' => 'View, create, edit, and delete barbers',
                ],
                'manage_schedules' => [
                    'label' => 'Manage Schedules',
                    'description' => 'Create and maintain branch barber schedules.',
                ],
                'record_walkins' => [
                    'label' => 'Record Walk-in Work',
                    'description' => 'Submit branch walk-in service completions.',
                ],
            ],
        ],
        'barber' => [
            'label' => 'Barber',
            'description' => 'Control what barbers can access in their own dashboard.',
            'features' => [
                'view_dashboard' => [
                    'label' => 'View Barber Dashboard',
                    'description' => 'Allow access to the barber dashboard page.',
                ],
                'update_appointment_status' => [
                    'label' => 'Update Appointment Status',
                    'description' => 'Allow Start and Complete actions for barber appointments.',
                ],
            ],
        ],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');
        $canManageBilling = $user->hasRole('Barbershop Admin');
        $canOperateBranch = $user->hasRole('Branch Manager');

        $tenant = null;
        $preferredDomain = '';
        $domainSuffix = 'localhost';
        $domainPreviewUrl = null;
        $subscription = null;
        $hasActivePlan = false;
        $mustContactAdminForReactivation = false;
        $dashboardAccessDefinitions = $this->dashboardAccessDefinitions();
        $dashboardAccessSettings = Tenant::dashboardAccessDefaults();
        $branchManagerAccess = $dashboardAccessSettings['branch_manager'] ?? [];
        $canManageUsers = $canManageBilling;
        $canManageServices = $canManageBilling || ($canOperateBranch && (bool) ($branchManagerAccess['manage_services'] ?? true));
        $canRecordWalkIns = $canOperateBranch
            && ! empty($user->branch_id)
            && (bool) ($branchManagerAccess['record_walkins'] ?? true);
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

            $dashboardAccessSettings = $tenant?->resolvedDashboardAccessSettings() ?? Tenant::dashboardAccessDefaults();
            $branchManagerAccess = $dashboardAccessSettings['branch_manager'] ?? [];
            $canManageUsers = $canManageBilling;
            $canManageServices = $canManageBilling || ($canOperateBranch && (bool) ($branchManagerAccess['manage_services'] ?? true));
            $canRecordWalkIns = $canOperateBranch
                && ! empty($user->branch_id)
                && (bool) ($branchManagerAccess['record_walkins'] ?? true);

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
                'availedServices' => collect(),
                'tenant' => $tenant,
                'subscription' => $subscription,
                'hasActivePlan' => $hasActivePlan,
                'mustContactAdminForReactivation' => $mustContactAdminForReactivation,
                'canManageBilling' => $canManageBilling,
                'canOperateBranch' => $canOperateBranch,
                'canManageUsers' => $canManageUsers,
                'canManageServices' => $canManageServices,
                'canRecordWalkIns' => $canRecordWalkIns,
                'planOptions' => $planOptions,
                'dashboardAccessDefinitions' => $dashboardAccessDefinitions,
                'dashboardAccessSettings' => $dashboardAccessSettings,
                'barbersForWalkIns' => collect(),
                'branchesForWalkIns' => collect(),
                'manageableBarbers' => collect(),
                'manageableCustomers' => collect(),
                'assignableBranches' => collect(),
                'pendingSystemReleases' => collect(),
                'supportTickets' => collect(),
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

        $availedPriceColumn = Schema::hasColumn('appointments', 'total_price')
            ? DB::raw('COALESCE(a.total_price, s.price, 0) as total_price')
            : DB::raw('COALESCE(s.price, 0) as total_price');

        $availedServices = $availedServicesQuery
            ->orderByDesc('a.appointment_datetime')
            ->orderByDesc('a.created_at')
            ->limit(20)
            ->get([
                'a.id',
                'a.appointment_datetime as booked_at',
                'a.status',
                $availedPriceColumn,
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
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'price', 'duration_minutes']);

        $barbersForWalkIns = collect();
        $branchesForWalkIns = collect();

        if ($canRecordWalkIns) {
            $barbersForWalkIns = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->role('Barber')
                ->when(
                    $user->hasRole('Branch Manager') && ! empty($user->branch_id),
                    fn ($query) => $query->where('branch_id', $user->branch_id)
                )
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id']);

            $branchesForWalkIns = Branch::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $manageableBarbers = collect();
        $manageableCustomers = collect();
        $assignableBranches = collect();

        $branchCashouts = collect();

        if ($canManageUsers) {
            $manageableBarbers = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->role('Barber')
                ->when(
                    $user->hasRole('Branch Manager') && ! empty($user->branch_id),
                    fn ($query) => $query->where('branch_id', $user->branch_id)
                )
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'branch_id', 'created_at']);

            $manageableCustomers = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->role('Customer')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'points_balance', 'created_at']);

            $assignableBranches = Branch::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->when(
                    $user->hasRole('Branch Manager') && ! empty($user->branch_id),
                    fn ($query) => $query->where('id', $user->branch_id)
                )
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if ($canOperateBranch && ! empty($user->branch_id)) {
            $branchCashouts = DB::table('barber_cashouts as bc')
                ->join('users as barber', 'barber.id', '=', 'bc.barber_id')
                ->where('bc.tenant_id', $tenantId)
                ->where('bc.branch_id', $user->branch_id)
                ->orderByRaw("bc.status = 'pending' desc")
                ->orderByDesc('bc.created_at')
                ->limit(30)
                ->get([
                    'bc.id',
                    'bc.points',
                    'bc.amount_php',
                    'bc.status',
                    'bc.created_at',
                    'bc.approved_at',
                    'bc.paid_at',
                    'bc.rejection_reason',
                    'barber.name as barber_name',
                ]);
        }

        $pendingSystemReleases = TenantSystemRelease::query()
            ->with(['systemRelease:id,version,display_version,publication_status,release_notes,published_at,commit_hash,short_commit,branch', 'appliedBy:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereIn('state', ['pending', 'held'])
            ->orderByRaw("state = 'pending' desc")
            ->orderByDesc('available_at')
            ->orderByDesc('id')
            ->get();

        $supportTickets = SupportTicket::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'messages' => fn ($query) => $query->with('sender:id,name')->orderBy('created_at'),
            ])
            ->orderByDesc('latest_reply_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

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
            'canOperateBranch' => $canOperateBranch,
            'canManageUsers' => $canManageUsers,
            'canManageServices' => $canManageServices,
            'canRecordWalkIns' => $canRecordWalkIns,
            'planOptions' => $planOptions,
            'dashboardAccessDefinitions' => $dashboardAccessDefinitions,
            'dashboardAccessSettings' => $dashboardAccessSettings,
            'barbersForWalkIns' => $barbersForWalkIns,
            'branchesForWalkIns' => $branchesForWalkIns,
            'manageableBarbers' => $manageableBarbers,
            'manageableCustomers' => $manageableCustomers,
            'assignableBranches' => $assignableBranches,
            'branchCashouts' => $branchCashouts,
            'pendingSystemReleases' => $pendingSystemReleases,
            'supportTickets' => $supportTickets,
        ]);
    }

    public function updateDashboardAccess(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('billing_error', 'No tenant found for this account.');
        }

        $validationRules = [];

        foreach (self::DASHBOARD_ACCESS_DEFINITIONS as $roleKey => $definition) {
            foreach (array_keys($definition['features']) as $featureKey) {
                $validationRules['access.'.$roleKey.'.'.$featureKey] = ['nullable', 'boolean'];
            }
        }

        $request->validate($validationRules);

        $resolvedSettings = [];

        foreach (Tenant::dashboardAccessDefaults() as $roleKey => $features) {
            foreach ($features as $featureKey => $defaultEnabled) {
                $resolvedSettings[$roleKey][$featureKey] = $request->boolean(
                    'access.'.$roleKey.'.'.$featureKey,
                    (bool) $defaultEnabled
                );
            }
        }

        $tenant = Tenant::query()->findOrFail($tenantId);
        $tenant->dashboard_access_settings = $resolvedSettings;
        $tenant->save();

        return redirect()->route('manager.dashboard')->with('billing_status', 'Dashboard RBAC settings updated successfully.');
    }

    public function storeManagedUser(Request $request): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! $actor->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($actor->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'No tenant is assigned to your account.');
        }

        $requestedRole = (string) $request->input('role');
        $emailRules = ['required', 'string', 'email', 'max:255', 'unique:users,email'];

        if ($requestedRole === 'Customer') {
            $emailRules = [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ];
        }

        $validated = $request->validate([
            'role' => ['required', 'in:Barber,Customer,Branch Manager'],
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'password' => ['required', 'string', 'min:8'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $targetRole = (string) $validated['role'];
        $branchId = null;

        if ($targetRole === 'Barber') {
            if (! empty($validated['branch_id'])) {
                $branchId = Branch::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('id', (int) $validated['branch_id'])
                    ->value('id');

                if (! $branchId) {
                    return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'Selected branch is invalid for this tenant.');
                }
            }

            try {
                app(TenantLimitValidator::class)->validateBarberCreation($tenantId);
            } catch (SubscriptionLimitExceededException $exception) {
                return redirect()->route('manager.dashboard')->with('user_mgmt_error', $exception->getMessage());
            }
        } elseif ($targetRole === 'Branch Manager') {
            if (empty($validated['branch_id'])) {
                return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'Branch is required when creating a branch manager account.');
            }

            $branchId = Branch::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('id', (int) $validated['branch_id'])
                ->value('id');

            if (! $branchId) {
                return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'Selected branch is invalid for this tenant.');
            }

            $branchAlreadyManaged = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->role('Branch Manager')
                ->exists();

            if ($branchAlreadyManaged) {
                return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'This branch already has a branch manager account.');
            }
        }

        $managedUser = User::query()
            ->withoutGlobalScopes()
            ->create([
                'tenant_id' => $tenantId,
                'branch_id' => in_array($targetRole, ['Barber', 'Branch Manager'], true) ? $branchId : null,
                'name' => (string) $validated['name'],
                'email' => strtolower((string) $validated['email']),
                'password' => Hash::make((string) $validated['password']),
            ]);

        Role::findOrCreate($targetRole, 'web');
        $managedUser->assignRole($targetRole);

        return redirect()->route('manager.dashboard')->with('user_mgmt_status', $targetRole.' account created successfully.');
    }

    public function updateManagedUserPassword(Request $request, int $userId): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! $actor->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($actor->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'No tenant is assigned to your account.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $managedUser = $this->resolveManageableUser($actor, $tenantId, $userId);

        if (! $managedUser) {
            return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'User not found or not manageable from your scope.');
        }

        $managedUser->password = Hash::make((string) $validated['password']);
        $managedUser->save();

        return redirect()->route('manager.dashboard')->with('user_mgmt_status', 'Password updated for '.$managedUser->name.'.');
    }

    public function destroyManagedUser(Request $request, int $userId): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! $actor->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($actor->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'No tenant is assigned to your account.');
        }

        $managedUser = $this->resolveManageableUser($actor, $tenantId, $userId);

        if (! $managedUser) {
            return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'User not found or not manageable from your scope.');
        }

        try {
            $managedUser->delete();
        } catch (\Throwable) {
            return redirect()->route('manager.dashboard')->with('user_mgmt_error', 'Unable to delete this user because related records still reference the account.');
        }

        return redirect()->route('manager.dashboard')->with('user_mgmt_status', 'User deleted successfully.');
    }

    public function updateDomain(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

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

    public function updateAppearance(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('billing_error', 'No tenant found for this account.');
        }

        $validated = $request->validate([
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'customer_theme' => ['required', Rule::in(['dark', 'light'])],
            'customer_font' => ['required', Rule::in(['dm_sans', 'poppins', 'space_grotesk', 'lora'])],
            'customer_button_style' => ['required', Rule::in(['rounded', 'pill', 'sharp'])],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_hero_image' => ['nullable', 'boolean'],
        ]);

        $tenant = Tenant::query()->findOrFail($tenantId);

        $logoPath = $tenant->logo_path;
        $heroImagePath = $tenant->hero_image_path;

        if ($request->boolean('remove_logo') && ! empty($logoPath)) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($request->boolean('remove_hero_image') && ! empty($heroImagePath)) {
            Storage::disk('public')->delete($heroImagePath);
            $heroImagePath = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')->store('tenants/'.$tenant->id.'/branding', 'public');
        }

        if ($request->hasFile('hero_image')) {
            if (! empty($heroImagePath)) {
                Storage::disk('public')->delete($heroImagePath);
            }

            $heroImagePath = $request->file('hero_image')->store('tenants/'.$tenant->id.'/branding', 'public');
        }

        $tenant->update([
            'brand_color' => $validated['brand_color'] ?? ($tenant->brand_color ?: '#C9A84C'),
            'brand_color_secondary' => $validated['brand_color_secondary'] ?? ($tenant->brand_color_secondary ?: '#B54B2A'),
            'customer_theme' => (string) $validated['customer_theme'],
            'customer_font' => (string) $validated['customer_font'],
            'customer_button_style' => (string) $validated['customer_button_style'],
            'logo_path' => $logoPath,
            'hero_image_path' => $heroImagePath,
        ]);

        return redirect()->route('manager.dashboard')->with('billing_status', 'Customer UI appearance updated successfully.');
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

    private function resolveManageableUser(User $actor, string $tenantId, int $userId): ?User
    {
        $managedUser = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $userId)
            ->role(['Barber', 'Customer'])
            ->first();

        if (! $managedUser) {
            return null;
        }

        if ($actor->hasRole('Branch Manager') && $managedUser->hasRole('Barber')) {
            if (empty($actor->branch_id) || (int) $managedUser->branch_id !== (int) $actor->branch_id) {
                return null;
            }
        }

        return $managedUser;
    }

    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    private function dashboardAccessDefinitions(): array
    {
        return self::DASHBOARD_ACCESS_DEFINITIONS;
    }
}
