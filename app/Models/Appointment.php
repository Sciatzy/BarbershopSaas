<?php

namespace App\Models;

use App\Events\AppointmentCompleted;
use App\Events\AppointmentConfirmedEvent;
use App\Events\ReviewSubmitted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\UsesTenantConnection;

class Appointment extends Model
{
    use UsesTenantConnection;

    protected static function booted(): void
    {
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
}
