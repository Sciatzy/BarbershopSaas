<?php

namespace App\Listeners;

use App\Events\AppointmentConfirmedEvent;
use App\Services\GoogleCalendarSyncService;

class SyncAppointmentToGoogleCalendar
{
    /**
     * Create the event listener.
     */
    public function __construct(private GoogleCalendarSyncService $googleCalendarSyncService) {}

    /**
     * Handle the event.
     */
    public function handle(AppointmentConfirmedEvent $event): void
    {
        $this->googleCalendarSyncService->syncAppointment($event->appointment);
    }
}
