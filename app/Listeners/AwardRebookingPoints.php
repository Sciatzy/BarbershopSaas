<?php

namespace App\Listeners;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class AwardRebookingPoints
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
    public function handle(object $event): void
    {
        if (! $event instanceof Appointment) {
            return;
        }

        $appointment = $event;

        if ((string) ($appointment->source ?? 'online') !== 'online') {
            return;
        }

        $hasPreviousCompletedAppointment = Appointment::query()
            ->where('customer_id', $appointment->customer_id)
            ->where('barber_id', $appointment->barber_id)
            ->where('status', 'completed')
            ->where('id', '!=', $appointment->id)
            ->exists();

        if (! $hasPreviousCompletedAppointment) {
            return;
        }

        $reason = 'Rebooking Points';

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
            'points_awarded' => 25,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
