<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\PointTransaction;
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

class BarberManagementController extends Controller
{
    public function __construct(
        private TenantLimitValidator $tenantLimitValidator,
        private TenantLifecycleNotifier $notifier,
        private TenantProvisioningService $tenantProvisioning,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');
        $isBranchManager = (bool) $user?->hasRole('Branch Manager');
        $managerBranchId = $isBranchManager ? (int) ($user->branch_id ?? 0) : null;

        $barbersQuery = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Barber')
            ->orderBy('name');

        if ($isBranchManager) {
            $barbersQuery->where('branch_id', $managerBranchId);
        }

        $barbers = $barbersQuery->get(['id', 'name', 'email', 'branch_id', 'created_at']);

        $branchesQuery = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name');

        if ($isBranchManager) {
            $branchesQuery->where('id', $managerBranchId);
        }

        $branches = $branchesQuery->get(['id', 'name']);

        $usage = $this->tenantLimitValidator->getTenantUsage($tenantId);

        return view('manager.barbers', [
            'barbers' => $barbers,
            'branches' => $branches,
            'usage' => $usage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $isBranchManager = $user->hasRole('Branch Manager');
        $managerBranchId = $isBranchManager ? (int) ($user->branch_id ?? 0) : null;

        if ($tenantId === '') {
            return back()->with('barber_error', 'No tenant is assigned to your account.');
        }

        if ($isBranchManager && ! $managerBranchId) {
            return back()->with('barber_error', 'Branch Manager account must be assigned to a branch before creating barber accounts.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $temporaryPassword = Str::password(16);

        $branchId = null;

        if ($isBranchManager) {
            $branchId = $managerBranchId;
        } elseif (! empty($validated['branch_id'])) {
            $branchId = Branch::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('id', (int) $validated['branch_id'])
                ->value('id');

            if (! $branchId) {
                return back()->with('barber_error', 'Selected branch is invalid for this tenant.');
            }
        }

        try {
            $this->tenantLimitValidator->validateBarberCreation($tenantId);
        } catch (SubscriptionLimitExceededException $exception) {
            return back()->with('barber_error', $exception->getMessage());
        }

        $barber = User::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
        ]);

        Role::findOrCreate('Barber', 'web');
        $barber->assignRole('Barber');

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
        $dashboardUrl = $this->tenantProvisioning->tenantUrl($assignedDomain, '/barber');

        $this->notifier->notifyUserWithDetails(
            $barber,
            'Your Barber Account Has Been Created',
            "Hi {$barber->name}, your barber account for {$tenantName} is now active.",
            [
                'Login Email' => $barber->email,
                'Temporary Password' => $temporaryPassword,
                'Assigned Domain' => $assignedDomain,
                'System URL' => $systemUrl,
                'Login URL' => $loginUrl,
                'Barber Dashboard URL' => $dashboardUrl,
            ],
            'For account security, please sign in and change your password immediately.'
        );

        return redirect()
            ->route('manager.barbers.index')
            ->with('barber_status', 'Barber account created successfully.');
    }

    public function update(Request $request, int $barberId): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $isBranchManager = $user->hasRole('Branch Manager');
        $managerBranchId = $isBranchManager ? (int) ($user->branch_id ?? 0) : null;

        if ($tenantId === '') {
            return back()->with('barber_error', 'No tenant is assigned to your account.');
        }

        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $barberId)
            ->role('Barber')
            ->first();

        if (! $barber) {
            return back()->with('barber_error', 'Selected barber account is invalid for this tenant.');
        }

        if ($isBranchManager && (int) ($barber->branch_id ?? 0) !== $managerBranchId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($barber->id)],
        ]);

        $barber->name = $validated['name'];
        $barber->email = $validated['email'];
        $barber->save();

        return back()->with('barber_status', 'Barber details updated successfully.');
    }

    public function destroy(Request $request, int $barberId): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $isBranchManager = $user->hasRole('Branch Manager');
        $managerBranchId = $isBranchManager ? (int) ($user->branch_id ?? 0) : null;

        if ($tenantId === '') {
            return back()->with('barber_error', 'No tenant is assigned to your account.');
        }

        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $barberId)
            ->role('Barber')
            ->first();

        if (! $barber) {
            return back()->with('barber_error', 'Selected barber account is invalid for this tenant.');
        }

        if ($isBranchManager && (int) ($barber->branch_id ?? 0) !== $managerBranchId) {
            abort(403);
        }

        $hasAppointments = Appointment::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barber->id)
            ->exists();

        if ($hasAppointments) {
            return back()->with('barber_error', 'Cannot delete barber with existing appointment records.');
        }

        $hasPointTransactions = PointTransaction::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barber->id)
            ->exists();

        if ($hasPointTransactions) {
            return back()->with('barber_error', 'Cannot delete barber with existing point transaction records.');
        }

        $barber->delete();

        return back()->with('barber_status', 'Barber account deleted successfully.');
    }

    public function updateBranchAssignment(Request $request, int $barberId): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return back()->with('barber_error', 'No tenant is assigned to your account.');
        }

        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
        ]);

        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $barberId)
            ->role('Barber')
            ->first();

        if (! $barber) {
            return back()->with('barber_error', 'Selected barber account is invalid for this tenant.');
        }

        $barber->branch_id = $validated['branch_id'] ?? null;
        $barber->save();

        return back()->with('barber_status', 'Barber branch assignment updated successfully.');
    }
}
