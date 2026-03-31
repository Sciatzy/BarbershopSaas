<?php

namespace App\Listeners;

use App\Events\AppointmentCompleted;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class AwardServicePoints
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
        $serviceType = Service::query()
            ->whereKey($appointment->service_id)
            ->value('type');

        if ($serviceType === 'standard') {
            $pointsAwarded = 10;
            $reason = 'Service Points: Standard';
        } elseif ($serviceType === 'premium') {
            $pointsAwarded = 15;
            $reason = 'Service Points: Premium';
        } else {
            return;
        }

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
            'points_awarded' => $pointsAwarded,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
