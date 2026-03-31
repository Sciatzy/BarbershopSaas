<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Branch;
use App\Models\User;
use App\Services\TenantLifecycleNotifier;
use App\Services\TenantLimitValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class BarberManagementController extends Controller
{
    public function __construct(
        private TenantLimitValidator $tenantLimitValidator,
        private TenantLifecycleNotifier $notifier,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        $barbers = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Barber')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id', 'created_at']);

        $branches = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

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
        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return back()->with('barber_error', 'No tenant is assigned to your account.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = null;

        if ($user->hasRole('Branch Manager')) {
            $branchId = $user->branch_id;
        } elseif (! empty($validated['branch_id'])) {
            $branchId = Branch::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('id', (int) $validated['branch_id'])
                ->value('id');
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
            'password' => Hash::make($validated['password']),
        ]);

        Role::findOrCreate('Barber', 'web');
        $barber->assignRole('Barber');

        $tenantName = (string) ($user->tenant?->name ?? 'your barbershop');

        $this->notifier->notifyUser(
            $barber,
            'Your Barber Account Credentials',
            "Hi {$barber->name}, your barber account for {$tenantName} is ready. "
            ."You can sign in using this email: {$barber->email} and password: {$validated['password']}. "
            .'Please change your password after your first login.'
        );

        return redirect()
            ->route('manager.barbers.index')
            ->with('barber_status', 'Barber account created successfully.');
    }
}
