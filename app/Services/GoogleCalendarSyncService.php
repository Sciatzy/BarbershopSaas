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

                $event = null;

                if (! empty($freshAppointment->google_calendar_event_id)) {
                    try {
                        $event = Event::find((string) $freshAppointment->google_calendar_event_id);
                    } catch (Throwable) {
                        $event = null;
                    }
                }

                if (! $event) {
                    $event = new Event();
                }

                $event->name = "Barbershop Appointment - {$barberName}";
                $event->description = "Service Type: {$serviceType}";
                $event->startDateTime = $startDateTime;
                $event->endDateTime = $endDateTime;
                $event->save();

                $googleEventId = (string) ($event->id ?? '');

                if ($googleEventId !== '' && (string) ($freshAppointment->google_calendar_event_id ?? '') !== $googleEventId) {
                    $freshAppointment->forceFill(['google_calendar_event_id' => $googleEventId])->saveQuietly();
                }
            } catch (Throwable $exception) {
                Log::error('Failed to sync appointment to Google Calendar.', [
                    'appointment_id' => $appointmentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        })->onQueue('calendar-sync');
    }

    /**
     * Best-effort cancellation sync: removes the matching Google Calendar event when possible.
     */
    public function syncCancellation($appointment): void
    {
        $appointmentId = $appointment instanceof Appointment
            ? $appointment->id
            : (int) $appointment;

        dispatch(function () use ($appointmentId): void {
            try {
                $freshAppointment = Appointment::query()
                    ->with(['barber:id,name', 'service:id,type,duration_minutes'])
                    ->find($appointmentId);

                if ($freshAppointment === null || $freshAppointment->appointment_datetime === null) {
                    return;
                }

                $deletedById = false;

                if (! empty($freshAppointment->google_calendar_event_id)) {
                    try {
                        $directEvent = Event::find((string) $freshAppointment->google_calendar_event_id);

                        if ($directEvent) {
                            $directEvent->delete();
                            $deletedById = true;
                        }
                    } catch (Throwable) {
                        $deletedById = false;
                    }

                    if ($deletedById) {
                        $freshAppointment->forceFill(['google_calendar_event_id' => null])->saveQuietly();

                        return;
                    }
                }

                $startDateTime = Carbon::parse($freshAppointment->appointment_datetime);
                $durationMinutes = max(1, (int) ($freshAppointment->service?->duration_minutes ?? 60));
                $expectedEndDateTime = $startDateTime->copy()->addMinutes($durationMinutes);
                $barberName = $freshAppointment->barber?->name ?? 'Barber';
                $expectedName = "Barbershop Appointment - {$barberName}";

                $events = Event::get(
                    $startDateTime->copy()->subHours(4),
                    $expectedEndDateTime->copy()->addHours(4)
                );

                foreach ($events as $event) {
                    $eventStart = isset($event->startDateTime) ? Carbon::parse((string) $event->startDateTime) : null;
                    $eventName = (string) ($event->name ?? '');

                    if ($eventStart === null) {
                        continue;
                    }

                    if ($eventName !== $expectedName) {
                        continue;
                    }

                    if ($eventStart->equalTo($startDateTime)) {
                        $event->delete();
                        $freshAppointment->forceFill(['google_calendar_event_id' => null])->saveQuietly();
                        break;
                    }
                }
            } catch (Throwable $exception) {
                Log::error('Failed to sync appointment cancellation to Google Calendar.', [
                    'appointment_id' => $appointmentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        })->onQueue('calendar-sync');
    }
}
