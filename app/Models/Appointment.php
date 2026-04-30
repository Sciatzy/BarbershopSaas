<?php

namespace App\Models;

use App\Events\AppointmentCompleted;
use App\Events\AppointmentConfirmedEvent;
use App\Events\ReviewSubmitted;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Tenancy\UsesTenantConnection;

class Appointment extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_id',
        'barber_id',
        'service_id',
        'staff_id',
        'proposed_replacement_barber_id',
        'appointment_datetime',
        'arrived_at',
        'late_marked_at',
        'no_show_marked_at',
        'booked_at',
        'completed_at',
        'google_calendar_event_id',
        'status',
        'total_price',
        'notes',
        'source',
        'created_by',
        'requires_customer_decision',
        'customer_decision_due_at',
        'emergency_reason',
        'is_on_time',
        'customer_rating',
        'customer_feedback',
        'work_notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_datetime' => 'datetime',
            'arrived_at' => 'datetime',
            'late_marked_at' => 'datetime',
            'no_show_marked_at' => 'datetime',
            'booked_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_price' => 'decimal:2',
            'requires_customer_decision' => 'boolean',
            'customer_decision_due_at' => 'datetime',
            'is_on_time' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('appointment_tenant_scope', function (Builder $builder): void {
            if (! app()->bound('tenant')) {
                return;
            }

            $tenant = app('tenant');

            if (! is_object($tenant) || empty($tenant->id)) {
                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenant->id);
        });

        static::created(function (self $appointment): void {
            if ($appointment->status === 'confirmed') {
                AppointmentConfirmedEvent::dispatch($appointment);
            }

            if ($appointment->status === 'completed') {
                AppointmentCompleted::dispatch($appointment);
            }

            if ($appointment->customer_rating !== null) {
                ReviewSubmitted::dispatch($appointment);
            }
        });

        static::updated(function (self $appointment): void {
            if ($appointment->wasChanged('status') && $appointment->status === 'confirmed') {
                AppointmentConfirmedEvent::dispatch($appointment);
            }

            if ($appointment->wasChanged('status') && $appointment->status === 'cancelled') {
                app(GoogleCalendarSyncService::class)->syncCancellation($appointment);
            }

            if ($appointment->wasChanged('status') && $appointment->status === 'completed') {
                AppointmentCompleted::dispatch($appointment);
            }

            if ($appointment->wasChanged('customer_rating') && $appointment->customer_rating !== null) {
                ReviewSubmitted::dispatch($appointment);
            }
        });
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function proposedReplacement(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_replacement_barber_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isCompletable(): bool
    {
        return in_array((string) $this->status, ['queued', 'in_progress'], true);
    }
}
