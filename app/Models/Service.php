<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\Tenancy\UsesTenantConnection;

class Service extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'base_price',
        'duration_min',
        'is_active',
        // Compatibility with existing service schema.
        'type',
        'price',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('service_tenant_scope', function (Builder $builder): void {
            if (! app()->bound('tenant')) {
                return;
            }

            $tenant = app('tenant');

            if (! is_object($tenant) || empty($tenant->id)) {
                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenant->id);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
