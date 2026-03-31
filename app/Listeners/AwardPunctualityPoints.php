<?php

namespace App\Listeners;

use App\Events\AppointmentCompleted;
use Illuminate\Support\Facades\DB;

class AwardPunctualityPoints
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentCompleted $event): void
    {
        $appointment = $event->appointment;

        if (! $appointment->is_on_time) {
            return;
        }

        $reason = 'Punctuality Points';

        $alreadyAwarded = DB::table('point_transactions')
            ->where('appointment_id', $appointment->id)
            ->where('barber_id', $appointment->barber_id)
            ->where('reason', $reason)
            ->exists();

        if ($alreadyAwarded) {
            return;
        }

        DB::table('point_transactions')->insert([
            'tenant_id' => $appointment->tenant_id,
            'barber_id' => $appointment->barber_id,
            'appointment_id' => $appointment->id,
            'points_awarded' => 5,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
