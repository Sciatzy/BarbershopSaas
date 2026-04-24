<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

class Tenant extends Model
{
    use Billable;

    public const DASHBOARD_ACCESS_DEFAULTS = [
        'branch_manager' => [
            'manage_services' => true,
            'manage_queue' => true,
            'manage_barbers' => true,
            'manage_schedules' => true,
            'record_walkins' => true,
        ],
        'barber' => [
            'view_dashboard' => true,
            'update_appointment_status' => true,
        ],
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'brand_color',
        'brand_color_secondary',
        'customer_theme',
        'customer_font',
        'customer_button_style',
        'logo_path',
        'hero_image_path',
        'dashboard_access_settings',
        'plan_tier',
        'status',
        'primary_domain',
        'database_name',
        'database_provisioned_at',
        'activated_at',
        'deactivated_at',
        'applied_system_version',
        'applied_system_version_at',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'dashboard_access_settings' => 'array',
            'database_provisioned_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'applied_system_version_at' => 'datetime',
        ];
    }

    public static function dashboardAccessDefaults(): array
    {
        return self::DASHBOARD_ACCESS_DEFAULTS;
    }

    public function resolvedDashboardAccessSettings(): array
    {
        $defaults = self::dashboardAccessDefaults();
        $stored = is_array($this->dashboard_access_settings) ? $this->dashboard_access_settings : [];
        $resolved = [];

        foreach ($defaults as $roleKey => $features) {
            $roleSettings = is_array($stored[$roleKey] ?? null) ? $stored[$roleKey] : [];

            foreach ($features as $featureKey => $defaultEnabled) {
                $rawValue = $roleSettings[$featureKey] ?? $defaultEnabled;

                $resolved[$roleKey][$featureKey] = match (true) {
                    is_bool($rawValue) => $rawValue,
                    is_int($rawValue), is_float($rawValue) => ((int) $rawValue) === 1,
                    is_string($rawValue) => in_array(strtolower($rawValue), ['1', 'true', 'yes', 'on'], true),
                    default => (bool) $defaultEnabled,
                };
            }
        }

        return $resolved;
    }

    public function dashboardFeatureEnabled(string $roleKey, string $featureKey, bool $default = true): bool
    {
        return (bool) ($this->resolvedDashboardAccessSettings()[$roleKey][$featureKey] ?? $default);
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

    public function systemReleaseStates(): HasMany
    {
        return $this->hasMany(TenantSystemRelease::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
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
