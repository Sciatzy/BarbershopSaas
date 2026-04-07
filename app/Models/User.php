<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\TenantLimitValidator;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            if (! empty($user->tenant_id)) {
                app(TenantLimitValidator::class)->forgetTenantCounts((string) $user->tenant_id);
            }
        });

        static::updated(function (self $user): void {
            $validator = app(TenantLimitValidator::class);

            if ($user->wasChanged('tenant_id')) {
                $originalTenantId = $user->getOriginal('tenant_id');

                if (! empty($originalTenantId)) {
                    $validator->forgetTenantCounts((string) $originalTenantId);
                }
            }

            if (! empty($user->tenant_id)) {
                $validator->forgetTenantCounts((string) $user->tenant_id);
            }
        });

        static::deleted(function (self $user): void {
            if (! empty($user->tenant_id)) {
                app(TenantLimitValidator::class)->forgetTenantCounts((string) $user->tenant_id);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'email',
        'password',
        'points_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points_balance' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
