<?php

namespace App\Providers;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Listeners\AwardRebookingPoints;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLimitValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Cashier;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);

        Event::listen('eloquent.created: '.Appointment::class, AwardRebookingPoints::class);

        Event::listen(RoleAttached::class, function (RoleAttached $event): void {
            if (! $event->model instanceof User || empty($event->model->tenant_id)) {
                return;
            }

            if (! $this->attachedRolesContainBarber($event->rolesOrIds)) {
                return;
            }

            $validator = app(TenantLimitValidator::class);
            $tenantId = (string) $event->model->tenant_id;

            $validator->forgetTenantCounts($tenantId);

            try {
                $validator->ensureBarberLimitNotExceeded($tenantId);
            } catch (SubscriptionLimitExceededException $exception) {
                $event->model->removeRole('Barber');

                throw $exception;
            }
        });

        Event::listen(RoleDetached::class, function (RoleDetached $event): void {
            if (! $event->model instanceof User || empty($event->model->tenant_id)) {
                return;
            }

            app(TenantLimitValidator::class)->forgetTenantCounts((string) $event->model->tenant_id);
        });
    }

    private function attachedRolesContainBarber(mixed $rolesOrIds): bool
    {
        $roleClass = app(PermissionRegistrar::class)->getRoleClass();
        $roleModel = new $roleClass();
        $roleKeyName = $roleModel->getKeyName();

        $roles = $rolesOrIds instanceof Collection ? $rolesOrIds->all() : Arr::wrap($rolesOrIds);

        if (collect($roles)->contains(fn (mixed $role): bool => is_string($role) && strtolower($role) === 'barber')) {
            return true;
        }

        $roleIds = collect($roles)
            ->map(function (mixed $role) {
                if (is_object($role) && method_exists($role, 'getKey')) {
                    return $role->getKey();
                }

                return $role;
            })
            ->filter(fn (mixed $role): bool => is_scalar($role))
            ->values()
            ->all();

        if ($roleIds === []) {
            return false;
        }

        return $roleClass::query()
            ->whereIn($roleKeyName, $roleIds)
            ->where('name', 'Barber')
            ->exists();
    }
}
