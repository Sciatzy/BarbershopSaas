<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function show(): View
    {
        $tenant = Tenant::query()
            ->where('status', 'active')
            ->first();

        $services = collect();
        $barbers = collect();

        if ($tenant) {
            $services = Service::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $barbers = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereHas('roles', fn ($query) => $query->where('name', 'Barber'))
                ->orderBy('name')
                ->get();
        }

        return view('public.landing', compact('tenant', 'services', 'barbers'));
    }
}
