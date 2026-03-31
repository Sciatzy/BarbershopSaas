<?php

namespace App\Models;

use App\Services\TenantLimitValidator;
use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\UsesTenantConnection;

class Branch extends Model
{
    use UsesTenantConnection;

    protected static function booted(): void
    {
        static::creating(function (self $branch): void {
            if (empty($branch->tenant_id)) {
                return;
            }

            app(TenantLimitValidator::class)->validateBranchCreation((string) $branch->tenant_id);
        });

        static::created(function (self $branch): void {
            if (! empty($branch->tenant_id)) {
                app(TenantLimitValidator::class)->forgetTenantCounts((string) $branch->tenant_id);
            }
        });

        static::updated(function (self $branch): void {
            $validator = app(TenantLimitValidator::class);

            if ($branch->wasChanged('tenant_id')) {
                $originalTenantId = $branch->getOriginal('tenant_id');

                if (! empty($originalTenantId)) {
                    $validator->forgetTenantCounts((string) $originalTenantId);
                }
            }

            if (! empty($branch->tenant_id)) {
                $validator->forgetTenantCounts((string) $branch->tenant_id);
            }
        });

        static::deleted(function (self $branch): void {
            if (! empty($branch->tenant_id)) {
                app(TenantLimitValidator::class)->forgetTenantCounts((string) $branch->tenant_id);
            }
        });
    }
}
