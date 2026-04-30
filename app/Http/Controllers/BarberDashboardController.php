<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PointTransaction;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BarberCashoutService;
use App\Services\PointsService;
use App\Services\TenantLifecycleNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarberDashboardController extends Controller
{
    private const LATE_GRACE_MINUTES = 10;

    public function __construct(private TenantLifecycleNotifier $notifier) {}

    public function index(Request $request, BarberCashoutService $cashoutService): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');
        $tenant = $tenantId !== '' ? Tenant::query()->find($tenantId) : null;
        $canUpdateAppointmentStatus = $tenant?->dashboardFeatureEnabled('barber', 'update_appointment_status', true) ?? true;

        if ($tenantId === '') {
            return view('barber.dashboard', [
                'scheduleToday' => collect(),
                'weekSchedule' => collect(),
                'appointmentsToday' => collect(),
                'upcomingAppointments' => collect(),
                'totalPoints' => 0,
                'cashoutTiers' => $cashoutService->tiers(),
                'cashoutHistory' => collect(),
                'canUpdateAppointmentStatus' => true,
            ]);
        }

        $today = Carbon::now();
        $todayDate = $today->toDateString();
        $dayOfWeek = (int) $today->dayOfWeek;

        $autoLateBookings = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->where('status', 'queued')
            ->whereDate('appointment_datetime', $todayDate)
            ->where('appointment_datetime', '<', Carbon::now()->subMinutes(self::LATE_GRACE_MINUTES))
            ->get();

        foreach ($autoLateBookings as $autoLateBooking) {
            /** @var Booking $autoLateBooking */
            $autoLateBooking->status = 'late';
            $autoLateBooking->late_marked_at = Carbon::now();
            $autoLateBooking->save();

            $this->notifyAttendanceStatusChange($autoLateBooking, 'late');
        }

        $scheduleToday = Schedule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working', true)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $weekdayLabels = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $weeklyScheduleByDay = Schedule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->where('is_working', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time', 'end_time'])
            ->groupBy(fn ($schedule) => (int) $schedule->day_of_week);

        $overrideEndDate = $today->copy()->addDays(6)->toDateString();

        $overridesByDate = ScheduleOverride::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->whereDate('schedule_date', '>=', $todayDate)
            ->whereDate('schedule_date', '<=', $overrideEndDate)
            ->get(['schedule_date', 'is_working', 'start_time', 'end_time'])
            ->keyBy(fn ($override) => (string) $override->schedule_date);

        $weekSchedule = collect(range(0, 6))->map(function (int $offset) use ($today, $weekdayLabels, $weeklyScheduleByDay, $overridesByDate) {
            $date = $today->copy()->addDays($offset);
            $dayOfWeek = (int) $date->dayOfWeek;
            $dateKey = $date->toDateString();
            $override = $overridesByDate->get($dateKey);

            if ($override) {
                $slots = collect();

                if ($override->is_working && ! empty($override->start_time) && ! empty($override->end_time)) {
                    $slots->push((object) [
                        'start_time' => $override->start_time,
                        'end_time' => $override->end_time,
                    ]);
                }
            } else {
                $slots = $weeklyScheduleByDay->get($dayOfWeek, collect());
            }

            return [
                'date' => $date,
                'label' => $weekdayLabels[$dayOfWeek] ?? $date->format('l'),
                'is_today' => $offset === 0,
                'slots' => $slots,
            ];
        });

        $appointmentsToday = DB::table('appointments as a')
            ->leftJoin('users as customer', 'customer.id', '=', 'a.customer_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->where('a.tenant_id', $tenantId)
            ->where('a.barber_id', $user->id)
            ->whereDate('a.appointment_datetime', $todayDate)
            ->orderBy('a.appointment_datetime')
            ->get([
                'a.id',
                'a.appointment_datetime',
                'a.booked_at',
                'a.created_at',
                'a.status',
                'a.arrived_at',
                'a.late_marked_at',
                'a.no_show_marked_at',
                'a.notes',
                'a.total_price',
                'customer.name as customer_name',
                's.name as service_name',
                's.duration_minutes as service_duration_minutes',
            ]);

        $upcomingAppointments = DB::table('appointments as a')
            ->leftJoin('users as customer', 'customer.id', '=', 'a.customer_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->where('a.tenant_id', $tenantId)
            ->where('a.barber_id', $user->id)
            ->whereIn('a.status', ['queued', 'late', 'arrived', 'in_progress'])
            ->where('a.appointment_datetime', '>', $today)
            ->orderBy('a.appointment_datetime')
            ->limit(20)
            ->get([
                'a.id',
                'a.appointment_datetime',
                'a.booked_at',
                'a.created_at',
                'a.status',
                'a.arrived_at',
                'a.late_marked_at',
                'a.no_show_marked_at',
                'a.completed_at',
                'a.notes',
                'a.total_price',
                'customer.name as customer_name',
                's.name as service_name',
                's.duration_minutes as service_duration_minutes',
            ]);

        $previousAppointments = DB::table('appointments as a')
            ->leftJoin('users as customer', 'customer.id', '=', 'a.customer_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->where('a.tenant_id', $tenantId)
            ->where('a.barber_id', $user->id)
            ->whereIn('a.status', ['completed', 'cancelled', 'no_show'])
            ->where('a.appointment_datetime', '<', $today)
            ->orderByDesc('a.appointment_datetime')
            ->limit(20)
            ->get([
                'a.id',
                'a.appointment_datetime',
                'a.booked_at',
                'a.created_at',
                'a.status',
                'a.arrived_at',
                'a.late_marked_at',
                'a.no_show_marked_at',
                'a.completed_at',
                'a.notes',
                'a.total_price',
                'customer.name as customer_name',
                's.name as service_name',
                's.duration_minutes as service_duration_minutes',
            ]);

        $totalPoints = PointTransaction::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->sum('points_awarded');

        $cashoutHistory = DB::table('barber_cashouts as bc')
            ->where('bc.tenant_id', $tenantId)
            ->where('bc.barber_id', $user->id)
            ->orderByDesc('bc.created_at')
            ->limit(10)
            ->get([
                'bc.id',
                'bc.points',
                'bc.amount_php',
                'bc.status',
                'bc.created_at',
                'bc.approved_at',
                'bc.paid_at',
                'bc.rejection_reason',
            ]);

        return view('barber.dashboard', [
            'scheduleToday' => $scheduleToday,
            'weekSchedule' => $weekSchedule,
            'appointmentsToday' => $appointmentsToday,
            'upcomingAppointments' => $upcomingAppointments,
            'previousAppointments' => $previousAppointments,
            'totalPoints' => $totalPoints,
            'cashoutTiers' => $cashoutService->tiers(),
            'cashoutHistory' => $cashoutHistory,
            'canUpdateAppointmentStatus' => $canUpdateAppointmentStatus,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking, PointsService $pointsService): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barber')) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:arrived,late,in_progress,completed,no_show'],
        ]);

        $tenantId = (string) ($user->tenant_id ?? '');

        if ((string) ($booking->tenant_id ?? '') !== $tenantId) {
            abort(403);
        }

        if ((int) ($booking->barber_id ?? 0) !== (int) $user->id) {
            abort(403);
        }

        $oldStatus = (string) ($booking->status ?? 'queued');
        $newStatus = (string) $validated['status'];

        if (in_array($oldStatus, ['completed', 'cancelled'], true)) {
            return back()->with('status', "Booking #{$booking->id} is already {$oldStatus}.");
        }

        if ($oldStatus === 'no_show') {
            return back()->with('status', "Booking #{$booking->id} is already marked as no-show.");
        }

        if ($oldStatus === $newStatus) {
            return back()->with('status', "Booking #{$booking->id} is already {$oldStatus}.");
        }

        if (in_array($oldStatus, ['arrived', 'late'], true) && in_array($newStatus, ['arrived', 'late'], true)) {
            return back()->withErrors(['status' => 'Use Start, Finish, or No-show for appointments already marked as arrived/late.']);
        }

        if ($oldStatus === 'in_progress' && in_array($newStatus, ['arrived', 'late', 'no_show'], true)) {
            return back()->withErrors(['status' => 'In-progress appointments can only be finished.']);
        }

        if ($newStatus === 'arrived' && $oldStatus !== 'queued') {
            return back()->withErrors(['status' => 'Only queued appointments can be marked arrived.']);
        }

        if ($newStatus === 'late' && $oldStatus !== 'queued') {
            return back()->withErrors(['status' => 'Only queued appointments can be marked late.']);
        }

        if ($newStatus === 'no_show' && ! in_array($oldStatus, ['queued', 'arrived', 'late'], true)) {
            return back()->withErrors(['status' => 'Only waiting appointments can be marked as no-show.']);
        }

        if ($newStatus === 'in_progress' && ! in_array($oldStatus, ['queued', 'arrived', 'late'], true)) {
            return back()->withErrors(['status' => 'Only waiting appointments can be started.']);
        }

        if ($newStatus === 'completed' && ! in_array($oldStatus, ['queued', 'arrived', 'late', 'in_progress'], true)) {
            return back()->withErrors(['status' => 'Only active appointments can be completed.']);
        }

        if ($newStatus === 'in_progress') {
            $serviceDurationMinutes = max(1, (int) ($booking->service?->duration_minutes ?? 30));
            $startAt = now()->greaterThan($booking->appointment_datetime)
                ? now()
                : $booking->appointment_datetime;
            $projectedEnd = $startAt->copy()->addMinutes($serviceDurationMinutes);

            $nextAppointment = Booking::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('barber_id', $user->id)
                ->where('id', '!=', $booking->id)
                ->whereIn('status', ['queued', 'arrived', 'late', 'in_progress'])
                ->where('appointment_datetime', '>', $booking->appointment_datetime)
                ->orderBy('appointment_datetime')
                ->first(['id', 'appointment_datetime']);

            if ($nextAppointment && $projectedEnd->greaterThan($nextAppointment->appointment_datetime)) {
                $nextTime = $nextAppointment->appointment_datetime->format('g:i A');

                return back()->withErrors([
                    'status' => "Starting now would overlap your next appointment at {$nextTime}. Reschedule or mark this booking as no-show.",
                ]);
            }
        }

        $booking->status = $newStatus;

        if ($newStatus === 'arrived') {
            $booking->arrived_at = $booking->arrived_at ?? now();
        }

        if ($newStatus === 'late') {
            $booking->late_marked_at = $booking->late_marked_at ?? now();
        }

        if ($newStatus === 'in_progress') {
            $booking->arrived_at = $booking->arrived_at ?? now();
        }

        if ($newStatus === 'no_show') {
            $booking->no_show_marked_at = $booking->no_show_marked_at ?? now();
            $booking->completed_at = null;
        }

        if ($newStatus === 'completed') {
            $booking->setAttribute('completed_at', now());
        }

        $booking->save();

        if (in_array($newStatus, ['late', 'no_show'], true)) {
            $this->notifyAttendanceStatusChange($booking, $newStatus);
        }

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $pointsAwarded = $pointsService->awardPoints($booking);

            return back()->with('status', "Booking #{$booking->id} completed. Customer earned {$pointsAwarded} points.");
        }

        if ($newStatus === 'no_show') {
            return back()->with('status', "Booking #{$booking->id} marked as no-show.");
        }

        return back()->with('status', "Booking #{$booking->id} marked as {$newStatus}.");
    }

    private function notifyAttendanceStatusChange(Booking $booking, string $newStatus): void
    {
        $booking->loadMissing([
            'customer:id,name,email',
            'barber:id,name',
            'service:id,name,type,duration_minutes',
        ]);

        $statusLabel = $newStatus === 'no_show' ? 'No-show' : 'Late';
        $scheduledAt = optional($booking->appointment_datetime)?->format('M d, Y g:i A') ?? 'N/A';
        $serviceName = (string) ($booking->service?->name ?? ucfirst((string) ($booking->service?->type ?? 'Service')));
        $barberName = (string) ($booking->barber?->name ?? 'Assigned barber');
        $customerName = (string) ($booking->customer?->name ?? 'Customer');

        if ($booking->customer) {
            $this->notifier->notifyUserWithDetails(
                $booking->customer,
                "Appointment Update: {$statusLabel}",
                "Your appointment status has been updated to {$statusLabel} by the shop.",
                [
                    'Booking ID' => (string) $booking->id,
                    'Status' => $statusLabel,
                    'Scheduled Time' => $scheduledAt,
                    'Service' => $serviceName,
                    'Barber' => $barberName,
                ],
                'If you need help rescheduling, please contact the shop or book again from your dashboard.'
            );
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $managerRecipients */
        $managerRecipients = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $booking->tenant_id)
            ->where(function ($query) use ($booking): void {
                $query->whereHas('roles', function ($roleQuery): void {
                    $roleQuery->whereIn('name', ['Barbershop Admin']);
                })->orWhere(function ($branchManagerQuery) use ($booking): void {
                    $branchManagerQuery
                        ->where('branch_id', (int) ($booking->branch_id ?? 0))
                        ->whereHas('roles', function ($roleQuery): void {
                            $roleQuery->whereIn('name', ['Branch Manager']);
                        });
                });
            })
            ->get(['id', 'name', 'email']);

        foreach ($managerRecipients as $manager) {
            $this->notifier->notifyUserWithDetails(
                $manager,
                "Barber Attendance Alert: {$statusLabel}",
                "An appointment in your shop was marked as {$statusLabel}.",
                [
                    'Booking ID' => (string) $booking->id,
                    'Customer' => $customerName,
                    'Barber' => $barberName,
                    'Service' => $serviceName,
                    'Scheduled Time' => $scheduledAt,
                    'Current Status' => $statusLabel,
                ],
                'Review the queue and rescheduling options from your manager dashboard if needed.'
            );
        }
    }
}
