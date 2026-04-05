<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\Tenant;

class ManagerSetupController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('Barbershop Admin') || !$user->tenant_id) {
            return redirect()->route('dashboard');
        }

        $tenant = Tenant::find($user->tenant_id);

        if (!$request->session()->has('pending_setup_plan')) {
            return redirect()->route('manager.dashboard');
        }

        $plan = $request->session()->get('pending_setup_plan');

        return view('manager.setup', [
            'tenant' => $tenant,
            'plan' => $plan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;
        
        if (!$tenantId) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'primary_domain' => [
                'required', 
                'string', 
                'max:50',
                'regex:/^[a-zA-Z0-9-]+$/'
            ],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/'
            ]
        ]);

        $tenant = Tenant::findOrFail($tenantId);
        
        $domainBase = config('app.url');
        // Extract the host part
        $host = parse_url($domainBase, PHP_URL_HOST) ?? 'barbershopsaas.test';
        $domainPathToSave = strtolower($request->primary_domain) . '.' . $host;

        if ($request->filled('custom_domain')) {
            $domainPathToSave = strtolower($request->custom_domain);
        }

        $tenant->update([
            'name' => $request->tenant_name,
            'primary_domain' => $domainPathToSave,
        ]);

        if ($request->session()->has('pending_setup_plan')) {
            $plan = $request->session()->pull('pending_setup_plan');
            $request->session()->put('auto_checkout_plan', $plan);
        }

        return redirect()->route('manager.dashboard')->with('status', 'Tenant customized successfully! Redirecting to checkout...');
    }
}