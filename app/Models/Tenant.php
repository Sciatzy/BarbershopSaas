<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Tenant extends Model
{
    use Billable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'plan_tier',
        'status',
        'primary_domain',
        'database_name',
        'database_provisioned_at',
        'activated_at',
        'deactivated_at',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'database_provisioned_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $tenant): void {
            if (empty($tenant->id)) {
                $tenant->id = (string) Str::uuid();
            }
        });
    }

    public function latestCashierSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id')->latestOfMany();
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function hasActivePlan(): bool
    {
        if (($this->status ?? 'pending') !== 'active') {
            return false;
        }

        $subscription = $this->latestCashierSubscription;

        if ($subscription === null) {
            return false;
        }

        if (! in_array((string) $subscription->stripe_status, ['active', 'trialing'], true)) {
            return false;
        }

        return $subscription->ends_at === null || $subscription->ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'pending') === 'active';
    }
}
