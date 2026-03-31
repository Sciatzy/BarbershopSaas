<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\PointTransaction;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        /** @var Tenant|null $resolvedTenant */
        $resolvedTenant = $request->attributes->get('currentTenant');

        $tenantId = null;

        if ($user !== null && ! $user->hasRole('Platform Admin')) {
            $tenantId = $user->tenant_id;
        }

        if ($tenantId === null && $resolvedTenant) {
            $tenantId = $resolvedTenant->id;
        }

        if ($tenantId !== null) {
            Branch::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
            User::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
            Service::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
            Appointment::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
            PointTransaction::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
        }

        return $next($request);
    }
}
