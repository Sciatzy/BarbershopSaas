<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantSchemaMigrationService
{
    public function migrateTenant(Tenant $tenant): void
    {
        $databaseName = trim((string) ($tenant->database_name ?? ''));

        if ($databaseName === '') {
            throw new \RuntimeException('Tenant database is not configured.');
        }

        $baseConnection = config('database.connections.mysql');

        if (! is_array($baseConnection) || $baseConnection === []) {
            throw new \RuntimeException('MySQL base connection is not configured.');
        }

        $connectionName = 'tenant_release_migration';
        $baseConnection['database'] = $databaseName;

        Config::set("database.connections.{$connectionName}", $baseConnection);
        DB::purge($connectionName);

        $exitCode = Artisan::call('migrate', [
            '--database' => $connectionName,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            $output = trim(Artisan::output());

            throw new \RuntimeException(
                $output !== ''
                    ? 'Tenant migration failed: '.$output
                    : 'Tenant migration failed with a non-zero exit code.'
            );
        }

        DB::disconnect($connectionName);
        DB::purge($connectionName);
    }
}