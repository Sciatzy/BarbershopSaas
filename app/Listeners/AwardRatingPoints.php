<?php

namespace App\Listeners;

use App\Events\ReviewSubmitted;
use Illuminate\Support\Facades\DB;

class AwardRatingPoints
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
    public function handle(ReviewSubmitted $event): void
    {
        $appointment = $event->appointment;

        if ((int) $appointment->customer_rating !== 5) {
            return;
        }

        $reason = 'Rating Points: 5-Star Review';

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
            'points_awarded' => 20,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
