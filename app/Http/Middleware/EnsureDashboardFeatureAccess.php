<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardFeatureAccess
{
    public function handle(Request $request, Closure $next, string $roleKey, string $featureKey): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('Barbershop Admin')) {
            return $next($request);
        }

        if ($roleKey === 'branch_manager' && ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        if ($roleKey === 'barber' && ! $user->hasRole('Barber')) {
            abort(403);
        }

        $tenant = null;

        if (! empty($user->tenant_id)) {
            $tenant = Tenant::query()->find((string) $user->tenant_id);
        }

        $isEnabled = $tenant?->dashboardFeatureEnabled($roleKey, $featureKey, true) ?? true;

        if (! $isEnabled) {
            abort(403, 'This dashboard feature is disabled by the shop owner.');
        }

        return $next($request);
    }
}
