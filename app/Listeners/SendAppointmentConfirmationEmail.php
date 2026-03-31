<?php

namespace App\Listeners;

use App\Events\AppointmentConfirmedEvent;
use App\Mail\AppointmentConfirmed as AppointmentConfirmedMail;
use Illuminate\Support\Facades\Mail;

class SendAppointmentConfirmationEmail
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
    public function handle(AppointmentConfirmedEvent $event): void
    {
        $appointment = $event->appointment;
        $appointment->loadMissing('customer:id,email');

        if (empty($appointment->customer?->email)) {
            return;
        }

        Mail::to($appointment->customer->email)
            ->queue(new AppointmentConfirmedMail($appointment));
    }
}
