<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasActivePlan
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->hasRole('Platform Admin')) {
            return $next($request);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->with('latestCashierSubscription')
            ->find($tenantId);

        $hasActivePlan = $tenant?->hasActivePlan() ?? false;

        if ($hasActivePlan) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Please select and activate a subscription plan before using this feature.',
            ], 402);
        }

        if ($user->hasRole('Barbershop Admin')) {
            $redirectRoute = 'billing.plans';
        } elseif ($user->hasRole('Branch Manager')) {
            $redirectRoute = 'manager.dashboard';
        } else {
            $redirectRoute = 'billing.plan-required';
        }

        return redirect()
            ->route($redirectRoute)
            ->with('plan_required', 'Please select and activate a subscription plan before using this feature.');
    }
}
