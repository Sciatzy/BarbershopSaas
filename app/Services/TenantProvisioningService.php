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
    public function ensureDomain(Tenant $tenant): string
    {
        $currentDomain = strtolower(trim((string) $tenant->primary_domain));

        if ($currentDomain !== '') {
            return $currentDomain;
        }

        $host = strtolower((string) parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST));

        if ($host === '') {
            $host = 'localhost';
        }

        $slug = Str::slug((string) $tenant->name);

        if ($slug === '') {
            $slug = 'tenant';
        }

        do {
            $candidate = sprintf('%s-%s.%s', $slug, strtolower(Str::random(6)), $host);
            $exists = Tenant::query()
                ->where('primary_domain', $candidate)
                ->where('id', '!=', $tenant->id)
                ->exists();
        } while ($exists);

        $tenant->forceFill(['primary_domain' => $candidate])->save();

        return $candidate;
    }

    public function suggestDatabaseName(string $tenantName): string
    {
        $slug = Str::of($tenantName)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();

        if ($slug === '') {
            $slug = 'tenant';
        }

        return substr('bs_tenant_'.$slug, 0, 64);
    }

    /**
     * @return array{ok:bool,message:string,database_name:string}
     */
    public function provisionDatabase(Tenant $tenant): array
    {
        $databaseName = $this->resolveProvisioningDatabaseName($tenant);

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

    private function resolveProvisioningDatabaseName(Tenant $tenant): string
    {
        $baseName = (string) ($tenant->database_name ?: $this->suggestDatabaseName((string) $tenant->name));
        $baseName = Str::of($baseName)
            ->lower()
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->trim('_')
            ->value();

        if ($baseName === '') {
            $baseName = 'bs_tenant_'.strtolower(Str::random(8));
        }

        $baseName = substr($baseName, 0, 64);

        $candidate = $baseName;
        $attempt = 0;

        while ($this->databaseNameAlreadyUsed($tenant, $candidate)) {
            $attempt++;
            $suffix = '_'.str_pad((string) $attempt, 2, '0', STR_PAD_LEFT);
            $candidate = substr($baseName, 0, 64 - strlen($suffix)).$suffix;

            if ($attempt > 99) {
                $suffix = '_'.strtolower(Str::random(6));
                $candidate = substr($baseName, 0, 64 - strlen($suffix)).$suffix;
                break;
            }
        }

        return $candidate;
    }

    private function databaseNameAlreadyUsed(Tenant $tenant, string $databaseName): bool
    {
        return Tenant::query()
            ->where('id', '!=', $tenant->id)
            ->whereRaw('LOWER(database_name) = ?', [strtolower($databaseName)])
            ->exists();
    }
}
