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

        if ($tenantId !== null && ! app()->bound('tenant')) {
            $tenant = $resolvedTenant;

            if (! $tenant) {
                $tenant = Tenant::query()->find($tenantId);
            }

            if ($tenant) {
                app()->instance('tenant', $tenant);
            }
        }

        if ($tenantId !== null) {
            Branch::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));

            // For Users, we must allow tenant_id IS NULL so that Platform Admin accounts (central app credentials)
            // can still be authenticated even when visiting a tenant domain.
            User::addGlobalScope('tenant_scope', function (Builder $builder) use ($tenantId) {
                $builder->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                          ->orWhereNull('tenant_id');
                });
            });

            Service::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
            Appointment::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
            PointTransaction::addGlobalScope('tenant_scope', fn (Builder $builder) => $builder->where('tenant_id', $tenantId));
        }

        return $next($request);
    }
}
