<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $lockedTenant = request()->attributes->get('currentTenant');

        if ($lockedTenant instanceof Tenant) {
            $tenants = collect([$lockedTenant]);
        } else {
            // Central-domain registration can select among active tenants.
            $tenants = Tenant::query()->where('status', 'active')->orderBy('name')->get();
            $lockedTenant = null;
        }

        return view('customer.auth.register', compact('tenants', 'lockedTenant'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $lockedTenant = $request->attributes->get('currentTenant');
        $tenantRules = ['required', 'string'];

        if ($lockedTenant instanceof Tenant) {
            if ((string) $lockedTenant->status !== 'active') {
                return back()->withErrors([
                    'tenant_id' => 'Customer registration is currently unavailable for this shop.',
                ])->withInput();
            }

            $request->merge(['tenant_id' => (string) $lockedTenant->id]);
            $tenantRules[] = Rule::in([(string) $lockedTenant->id]);
        } else {
            $tenantRules[] = Rule::exists('tenants', 'id')->where(
                fn ($query) => $query->where('status', 'active')
            );
        }

        $targetTenantId = (string) $request->input('tenant_id');
        $emailRules = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            Rule::unique('users', 'email')->where(
                fn ($query) => $query->where('tenant_id', $targetTenantId)
            ),
        ];

        $request->validate([
            'tenant_id' => $tenantRules,
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request): User {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $request->tenant_id,
            ]);

            // Ensure Customer role exists and assign it
            Role::findOrCreate('Customer', 'web');
            $user->assignRole('Customer');

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('customer.dashboard')->with('success', 'Account created successfully. Welcome!');
    }
}
