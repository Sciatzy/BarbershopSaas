<?php

namespace App\Http\Controllers\Manager;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLifecycleNotifier;
use App\Services\TenantLimitValidator;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class BranchController extends Controller
{
    public function __construct(
        private TenantLimitValidator $tenantLimitValidator,
        private TenantLifecycleNotifier $notifier,
        private TenantProvisioningService $tenantProvisioning,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $planRedirect = $this->ensureOwnerHasActivePlan($request);
        if ($planRedirect instanceof RedirectResponse) {
            return $planRedirect;
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        $branches = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'created_at']);

        $branchManagers = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Branch Manager')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'branch_id'])
            ->keyBy('branch_id');

        $usage = $this->tenantLimitValidator->getTenantUsage($tenantId);

        return view('manager.branches.index', [
            'branches' => $branches,
            'branchManagers' => $branchManagers,
            'usage' => $usage,
        ]);
    }

    public function assignManager(Request $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $planRedirect = $this->ensureOwnerHasActivePlan($request);
        if ($planRedirect instanceof RedirectResponse) {
            return $planRedirect;
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        abort_if((string) ($branch->tenant_id ?? '') !== $tenantId, 403);

        $validated = $request->validate([
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        $existingBranchManager = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->role('Branch Manager')
            ->exists();

        if ($existingBranchManager) {
            return back()->with('manager_error', 'This branch already has a branch manager account.');
        }

        $temporaryPassword = Str::password(16);

        $manager = User::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $branch->id,
            'name' => $validated['manager_name'],
            'email' => $validated['manager_email'],
            'password' => Hash::make($temporaryPassword),
        ]);

        Role::findOrCreate('Branch Manager', 'web');
        $manager->assignRole('Branch Manager');

        $tenant = $user->tenant;
        $tenantName = (string) ($tenant?->name ?? 'your barbershop');
        $assignedDomain = strtolower((string) ($tenant?->primary_domain ?? ''));

        if ($assignedDomain === '') {
            $assignedDomain = strtolower((string) $request->getHost());
            $assignedDomain = preg_replace('/^www\./', '', $assignedDomain) ?? $assignedDomain;
        }

        if ($assignedDomain === '') {
            $assignedDomain = (string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
        }

        $systemUrl = $this->tenantProvisioning->tenantUrl($assignedDomain);
        $loginUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/login');
        $dashboardUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/manager');

        $this->notifier->notifyUserWithDetails(
            $manager,
            'Your Branch Manager Account Has Been Created',
            "Hi {$manager->name}, your branch manager account for {$tenantName} is now active.",
            [
                'Assigned Branch' => (string) $branch->name,
                'Login Email' => $manager->email,
                'Temporary Password' => $temporaryPassword,
                'Assigned Domain' => $assignedDomain,
                'System URL' => $systemUrl,
                'Login URL' => $loginUrl,
                'Manager Dashboard URL' => $dashboardUrl,
            ],
            'For account security, please sign in and change your password immediately.'
        );

        return redirect()
            ->route('manager.branches.index')
            ->with('manager_status', 'Branch manager account created and emailed successfully.');
    }

    public function updateManager(Request $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $planRedirect = $this->ensureOwnerHasActivePlan($request);
        if ($planRedirect instanceof RedirectResponse) {
            return $planRedirect;
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        abort_if((string) ($branch->tenant_id ?? '') !== $tenantId, 403);

        $manager = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->role('Branch Manager')
            ->first();

        if (! $manager) {
            return back()->with('manager_error', 'No branch manager is currently assigned to this branch.');
        }

        $validated = $request->validate([
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($manager->id),
            ],
        ]);

        $previousName = (string) $manager->name;
        $previousEmail = (string) $manager->email;
        $temporaryPassword = Str::password(16);

        $manager->name = $validated['manager_name'];
        $manager->email = $validated['manager_email'];
        $manager->password = Hash::make($temporaryPassword);
        $manager->save();

        $tenant = $user->tenant;
        $tenantName = (string) ($tenant?->name ?? 'your barbershop');
        $assignedDomain = strtolower((string) ($tenant?->primary_domain ?? ''));

        if ($assignedDomain === '') {
            $assignedDomain = strtolower((string) $request->getHost());
            $assignedDomain = preg_replace('/^www\./', '', $assignedDomain) ?? $assignedDomain;
        }

        if ($assignedDomain === '') {
            $assignedDomain = (string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
        }

        $loginUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/login');
        $dashboardUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/manager');

        $details = [
            'Assigned Branch' => (string) $branch->name,
            'Updated Name' => (string) $manager->name,
            'Updated Email' => (string) $manager->email,
            'Temporary Password' => $temporaryPassword,
            'Assigned Domain' => $assignedDomain,
            'Login URL' => $loginUrl,
            'Manager Dashboard URL' => $dashboardUrl,
        ];

        if ($previousName !== (string) $manager->name) {
            $details['Previous Name'] = $previousName;
        }

        if ($previousEmail !== (string) $manager->email) {
            $details['Previous Email'] = $previousEmail;
        }

        $this->notifier->notifyUserWithDetails(
            $manager,
            'Your Branch Manager Profile Was Updated',
            "Hi {$manager->name}, your branch manager profile for {$tenantName} was updated by the barbershop owner. A new temporary password has been generated for your account.",
            $details,
            'Please sign in using the temporary password and change it immediately. If you did not expect this change, contact your barbershop admin immediately.'
        );

        return redirect()
            ->route('manager.branches.index')
            ->with('manager_status', 'Branch manager details updated successfully. A temporary password was emailed to the manager.');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $planRedirect = $this->ensureOwnerHasActivePlan($request);
        if ($planRedirect instanceof RedirectResponse) {
            return $planRedirect;
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return back()->with('branch_error', 'No tenant is assigned to your account.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255', 'required_with:manager_email'],
            'manager_email' => ['nullable', 'string', 'email', 'max:255', 'required_with:manager_name', 'unique:users,email'],
        ]);

        try {
            $this->tenantLimitValidator->validateBranchCreation($tenantId);
        } catch (SubscriptionLimitExceededException $exception) {
            return back()->with('branch_error', $exception->getMessage());
        }

        $branch = new Branch();
        $branch->tenant_id = $tenantId;
        $branch->name = $validated['name'];
        $branch->address = $validated['address'];
        $branch->save();

        $managerName = (string) ($validated['manager_name'] ?? '');
        $managerEmail = (string) ($validated['manager_email'] ?? '');

        if ($managerName !== '' && $managerEmail !== '') {
            $temporaryPassword = Str::password(16);

            $manager = User::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branch->id,
                'name' => $managerName,
                'email' => $managerEmail,
                'password' => Hash::make($temporaryPassword),
            ]);

            Role::findOrCreate('Branch Manager', 'web');
            $manager->assignRole('Branch Manager');

            $tenant = $user->tenant;
            $tenantName = (string) ($tenant?->name ?? 'your barbershop');
            $assignedDomain = strtolower((string) ($tenant?->primary_domain ?? ''));

            if ($assignedDomain === '') {
                $assignedDomain = strtolower((string) $request->getHost());
                $assignedDomain = preg_replace('/^www\./', '', $assignedDomain) ?? $assignedDomain;
            }

            if ($assignedDomain === '') {
                $assignedDomain = (string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
            }

            $systemUrl = $this->tenantProvisioning->tenantUrl($assignedDomain);
            $loginUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/login');
            $dashboardUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/manager');

            $this->notifier->notifyUserWithDetails(
                $manager,
                'Your Branch Manager Account Has Been Created',
                "Hi {$manager->name}, your branch manager account for {$tenantName} is now active.",
                [
                    'Assigned Branch' => (string) $branch->name,
                    'Login Email' => $manager->email,
                    'Temporary Password' => $temporaryPassword,
                    'Assigned Domain' => $assignedDomain,
                    'System URL' => $systemUrl,
                    'Login URL' => $loginUrl,
                    'Manager Dashboard URL' => $dashboardUrl,
                ],
                'For account security, please sign in and change your password immediately.'
            );

            return redirect()
                ->route('manager.branches.index')
                ->with('branch_status', 'Branch and branch manager account created successfully.');
        }

        return redirect()
            ->route('manager.branches.index')
            ->with('branch_status', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $planRedirect = $this->ensureOwnerHasActivePlan($request);
        if ($planRedirect instanceof RedirectResponse) {
            return $planRedirect;
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        abort_if((string) ($branch->tenant_id ?? '') !== $tenantId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        $branch->name = $validated['name'];
        $branch->address = $validated['address'];
        $branch->save();

        return redirect()
            ->route('manager.branches.index')
            ->with('branch_status', 'Branch updated successfully.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $planRedirect = $this->ensureOwnerHasActivePlan($request);
        if ($planRedirect instanceof RedirectResponse) {
            return $planRedirect;
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        abort_if((string) ($branch->tenant_id ?? '') !== $tenantId, 403);

        $hasAssignedUsers = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->exists();

        if ($hasAssignedUsers) {
            return back()->with('branch_error', 'Cannot delete branch with assigned users. Reassign branch users first.');
        }

        $hasAppointments = Appointment::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->exists();

        if ($hasAppointments) {
            return back()->with('branch_error', 'Cannot delete branch with appointment records.');
        }

        $branch->delete();

        return redirect()
            ->route('manager.branches.index')
            ->with('branch_status', 'Branch deleted successfully.');
    }

    private function ensureOwnerHasActivePlan(Request $request): ?RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            return null;
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()
                ->route('manager.dashboard')
                ->with('plan_required', 'Please select and activate a subscription plan before using branch management.');
        }

        $tenant = Tenant::query()
            ->with('latestCashierSubscription')
            ->find($tenantId);

        if (! ($tenant?->hasActivePlan() ?? false)) {
            return redirect()
                ->route('billing.plans')
                ->with('plan_required', 'Please select and activate a subscription plan before using branch management.');
        }

        return null;
    }
}
