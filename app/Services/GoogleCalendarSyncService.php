<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\GoogleCalendar\Event;
use Throwable;

class GoogleCalendarSyncService
{
    /**
     * Queue Google Calendar synchronization for an appointment.
     */
    public function syncAppointment($appointment): void
    {
        $appointmentId = $appointment instanceof Appointment
            ? $appointment->id
            : (int) $appointment;

        dispatch(function () use ($appointmentId): void {
            try {
                $freshAppointment = Appointment::query()
                    ->with(['barber:id,name', 'service:id,type,duration_minutes'])
                    ->find($appointmentId);

                if ($freshAppointment === null) {
                    return;
                }

                $startDateTime = Carbon::parse($freshAppointment->appointment_datetime);
                $durationMinutes = max(1, (int) ($freshAppointment->service?->duration_minutes ?? 60));
                $endDateTime = $startDateTime->copy()->addMinutes($durationMinutes);

                $barberName = $freshAppointment->barber?->name ?? 'Barber';
                $serviceType = ucfirst((string) ($freshAppointment->service?->type ?? 'Service'));

                $event = new Event();
                $event->name = "Barbershop Appointment - {$barberName}";
                $event->description = "Service Type: {$serviceType}";
                $event->startDateTime = $startDateTime;
                $event->endDateTime = $endDateTime;
                $event->save();
            } catch (Throwable $exception) {
                Log::error('Failed to sync appointment to Google Calendar.', [
                    'appointment_id' => $appointmentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        })->onQueue('calendar-sync');
    }
}
