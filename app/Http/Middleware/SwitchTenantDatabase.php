<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SwitchTenantDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('tenancy.switch_database', false)) {
            return $next($request);
        }

        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('currentTenant');

        if (! $tenant) {
            return $next($request);
        }

        $databaseName = (string) ($tenant->database_name ?? '');

        if ($databaseName === '') {
            return $next($request);
        }

        $mysqlConnection = config('database.connections.mysql');

        if (! is_array($mysqlConnection) || $mysqlConnection === []) {
            return $next($request);
        }

        $tenantConnectionConfig = $mysqlConnection;
        $tenantConnectionConfig['database'] = $databaseName;

        Config::set('database.connections.tenant', $tenantConnectionConfig);

        DB::purge('tenant');
        DB::reconnect('tenant');

        app()->instance('tenantConnectionName', 'tenant');

        return $next($request);
    }
}
