<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TenantProvisioningService
{
    public function suggestDatabaseName(string $tenantName): string
    {
        $slug = Str::of($tenantName)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();

        if ($slug === '') {
            $slug = 'tenant';
        }

        return 'bs_tenant_'.$slug;
    }

    /**
     * @return array{ok:bool,message:string,database_name:string}
     */
    public function provisionDatabase(Tenant $tenant): array
    {
        $databaseName = (string) ($tenant->database_name ?: $this->suggestDatabaseName((string) $tenant->name));

        try {
            DB::statement(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', str_replace('`', '', $databaseName)));

            $tenantConnection = $this->configureTenantConnection($databaseName);
            $this->runTenantMigrations($tenantConnection);
            $this->seedTenantRoles($tenantConnection);

            $tenant->forceFill([
                'database_name' => $databaseName,
                'database_provisioned_at' => now(),
            ])->save();

            return [
                'ok' => true,
                'message' => 'Tenant database provisioned and schema bootstrapped.',
                'database_name' => $databaseName,
            ];
        } catch (Throwable $exception) {
            $tenant->forceFill([
                'database_name' => $databaseName,
            ])->save();

            return [
                'ok' => false,
                'message' => 'Unable to auto-create DB on this MySQL user. Saved database name for manual provisioning: '.$databaseName,
                'database_name' => $databaseName,
            ];
        }
    }

    private function configureTenantConnection(string $databaseName): string
    {
        $base = config('database.connections.mysql');

        if (! is_array($base) || $base === []) {
            throw new \RuntimeException('MySQL base connection is not configured.');
        }

        $connectionName = 'tenant_provisioning';
        $base['database'] = $databaseName;

        Config::set("database.connections.{$connectionName}", $base);

        DB::purge($connectionName);

        return $connectionName;
    }

    private function runTenantMigrations(string $connectionName): void
    {
        Artisan::call('migrate', [
            '--database' => $connectionName,
            '--force' => true,
        ]);
    }

    private function seedTenantRoles(string $connectionName): void
    {
        $roles = [
            'Platform Admin',
            'Barbershop Admin',
            'Branch Manager',
            'Barber',
            'Customer',
        ];

        foreach ($roles as $role) {
            DB::connection($connectionName)->table('roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'web'],
                [
                    'name' => $role,
                    'guard_name' => 'web',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
