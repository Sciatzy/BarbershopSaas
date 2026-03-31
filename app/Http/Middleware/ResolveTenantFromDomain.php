<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->getHost());
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        $centralDomains = config('tenancy.central_domains', []);

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->whereRaw('LOWER(primary_domain) = ?', [$host])
            ->first();

        if (! $tenant) {
            if ((bool) config('tenancy.abort_if_unknown_domain', false)) {
                abort(404, 'Tenant domain not found.');
            }

            return $next($request);
        }

        app()->instance('currentTenant', $tenant);
        $request->attributes->set('currentTenant', $tenant);

        return $next($request);
    }
}
