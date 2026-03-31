<?php

namespace App\Support\Tenancy;

trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        $tenantConnection = app()->bound('tenantConnectionName')
            ? app('tenantConnectionName')
            : null;

        if (is_string($tenantConnection) && $tenantConnection !== '') {
            return $tenantConnection;
        }

        return $this->connection;
    }
}
