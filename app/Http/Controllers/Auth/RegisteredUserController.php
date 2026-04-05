<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLifecycleNotifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function __construct(private TenantLifecycleNotifier $notifier) {}

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request): User {
            $tenant = Tenant::query()->create([
                'name' => trim((string) $request->name)."'s Barbershop",
                'plan_tier' => 'starter',
                'status' => 'pending',
                'primary_domain' => null,
                'database_name' => null,
                'activated_at' => null,
                'deactivated_at' => null,
            ]);

            $user = User::query()->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
            ]);

            Role::findOrCreate('Barbershop Admin', 'web');
            $user->assignRole('Barbershop Admin');

            $tenant->forceFill(['owner_user_id' => $user->id])->save();

            $this->notifier->notifyUserWithDetails(
                $user,
                'Registration Received - Pending Activation',
                "Hi {$user->name}, your account registration has been received.",
                [
                    'Account Status' => 'Pending',
                    'Tenant Name' => (string) $tenant->name,
                    'Next Step' => 'Wait for platform admin approval or subscribe to a plan to activate your account.',
                    'Login URL' => (string) route('login'),
                    'Billing Plans URL' => (string) route('billing.plans'),
                ],
                'Database and domain provisioning will only happen after activation.'
            );

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
