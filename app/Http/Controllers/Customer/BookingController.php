<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BookingController extends Controller
{
    private const SLOT_STEP_MINUTES = 15;
    private const DEFAULT_SLOT_INTERVAL_MINUTES = 30;

    public function index(Request $request): View
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');
        $bookingSortColumn = Schema::hasColumn('bookings', 'booked_at') ? 'booked_at' : 'created_at';

        $bookings = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->with(['service', 'staff', 'barber', 'proposedReplacement'])
            ->latest($bookingSortColumn)
            ->latest('created_at')
            ->get();

        return view('customer.bookings.index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');

        $selectedDate = (string) $request->query('date', now()->toDateString());
        $selectedBranchId = (int) $request->query('branch_id', old('branch_id', 0));
        $selectedServiceId = (int) $request->query('service_id', old('service_id', 0));
        $selectedBarberId = (int) $request->query('staff_id', old('staff_id', 0));

        $branches = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        if ($selectedBranchId > 0 && ! $branches->contains(fn ($branch) => (int) $branch->id === $selectedBranchId)) {
            $selectedBranchId = 0;
        }

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        if ($selectedServiceId > 0 && ! $services->contains(fn ($service) => (int) $service->id === $selectedServiceId)) {
            $selectedServiceId = 0;
        }

        $barbersQuery = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Barber')
            ->orderBy('name');

        if ($selectedBranchId > 0) {
            $barbersQuery->where('branch_id', $selectedBranchId);
        }

        $barbers = $barbersQuery->get(['id', 'name', 'branch_id']);

        if ($selectedBarberId > 0 && ! $barbers->contains(fn ($barber) => (int) $barber->id === $selectedBarberId)) {
            $selectedBarberId = 0;
        }

        $routeServiceId = (int) ($request->route('service') ?? 0);
        $selectedServiceId = $routeServiceId > 0 ? $routeServiceId : $selectedServiceId;

        $availableSlots = $this->availableSlotsForDate(
            $tenantId,
            $selectedBarberId,
            $selectedDate,
            $selectedServiceId,
        );

        return view('customer.bookings.create', [
            'branches' => $branches,
            'services' => $services,
            'barbers' => $barbers,
            'selectedServiceId' => $selectedServiceId,
            'selectedDate' => $selectedDate,
            'selectedBranchId' => $selectedBranchId,
            'selectedBarberId' => $selectedBarberId,
            'availableSlots' => $availableSlots,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');

        $validated = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],
            'service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
            'staff_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $service = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->findOrFail($validated['service_id']);

        $branch = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($validated['branch_id']);

        $staffId = (int) $validated['staff_id'];

        User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->role('Barber')
            ->findOrFail($staffId);

        $appointmentDateTime = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time']
        );

        if ($appointmentDateTime->lessThanOrEqualTo(now())) {
            return back()->withErrors(['appointment_time' => 'Please choose a future appointment time.'])->withInput();
        }

        $availableSlots = $this->availableSlotsForDate($tenantId, $staffId, $validated['appointment_date'], (int) $service->id);
        $slotValues = $availableSlots->pluck('value');

        if (! $slotValues->contains((string) $validated['appointment_time'])) {
            return back()->withErrors(['appointment_time' => 'Selected time slot is no longer available.'])->withInput();
        }

        $price = (float) ($service->base_price ?? $service->price ?? 0);

        $barberId = $staffId;

        try {
            $booking = DB::transaction(function () use ($tenantId, $customer, $service, $branch, $staffId, $barberId, $price, $validated, $appointmentDateTime): Booking {
                $existingActiveBooking = Booking::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', ['queued', 'confirmed', 'in_progress'])
                    ->lockForUpdate()
                    ->first();

                if ($existingActiveBooking !== null) {
                    throw new \RuntimeException('You already have an active booking. Please complete, cancel, or reschedule it first.');
                }

                $duplicateRecentBooking = Booking::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('customer_id', $customer->id)
                    ->where('service_id', $service->id)
                    ->where('barber_id', $barberId)
                    ->where('status', 'queued')
                    ->where('created_at', '>=', now()->subMinutes(2))
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateRecentBooking) {
                    throw new \RuntimeException('Duplicate booking detected. Please wait a moment before trying again.');
                }

                return Booking::query()->create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branch->id,
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'staff_id' => $staffId,
                    'barber_id' => $barberId,
                    'total_price' => $price,
                    'status' => 'queued',
                    'booked_at' => now(),
                    'appointment_datetime' => Carbon::instance($appointmentDateTime),
                    'notes' => $validated['notes'] ?? null,
                    'source' => 'online',
                    'created_by' => $customer->id,
                    'is_on_time' => false,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()])->withInput();
        } catch (\Throwable) {
            return back()->withErrors(['booking' => 'Unable to create booking right now. Please try again.'])->withInput();
        }

        return redirect()->route('customer.bookings')
            ->with('status', "Appointment booked for {$appointmentDateTime->format('M d, Y g:i A')}! Booking #{$booking->id} created.");
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $booking = $this->resolveOwnedBooking($request, $booking);

        if (in_array((string) $booking->status, ['completed', 'cancelled'], true)) {
            return back()->with('status', 'This booking can no longer be cancelled.');
        }

        if ((string) $booking->status === 'in_progress') {
            return back()->with('status', 'In-progress bookings cannot be cancelled online.');
        }

        $booking->status = 'cancelled';
        $booking->save();

        return back()->with('status', 'Booking cancelled successfully.');
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        $booking = $this->resolveOwnedBooking($request, $booking);

        if (! in_array((string) $booking->status, ['queued', 'confirmed'], true)) {
            return back()->withErrors(['reschedule' => 'Only queued or confirmed bookings can be rescheduled.']);
        }

        $validated = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        $newDateTime = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time']
        );

        if ($newDateTime->lessThanOrEqualTo(now())) {
            return back()->withErrors(['reschedule' => 'Please choose a future date and time.']);
        }

        $tenantId = (string) ($booking->tenant_id ?? '');
        $barberId = (int) ($booking->barber_id ?? $booking->staff_id ?? 0);
        $serviceId = (int) ($booking->service_id ?? 0);

        if ($tenantId === '' || $barberId <= 0 || $serviceId <= 0) {
            return back()->withErrors(['reschedule' => 'Unable to validate availability for this booking. Please contact support.']);
        }

        $availableSlots = $this->availableSlotsForDate(
            $tenantId,
            $barberId,
            $validated['appointment_date'],
            $serviceId,
            (int) $booking->id,
        );

        if (! $availableSlots->pluck('value')->contains((string) $validated['appointment_time'])) {
            return back()->withErrors(['reschedule' => 'Selected time slot is no longer available. Please choose another slot.']);
        }

        $booking->appointment_datetime = Carbon::instance($newDateTime);
        $booking->save();

        return back()->with('status', 'Booking rescheduled successfully.');
    }

    public function submitFeedback(Request $request, Booking $booking): RedirectResponse
    {
        $booking = $this->resolveOwnedBooking($request, $booking);

        if ((string) $booking->status !== 'completed') {
            return back()->withErrors(['feedback' => 'Feedback can only be submitted for completed bookings.']);
        }

        $validated = $request->validate([
            'customer_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'customer_feedback' => ['nullable', 'string', 'max:500'],
        ]);

        $hasRating = array_key_exists('customer_rating', $validated) && $validated['customer_rating'] !== null;
        $hasFeedback = array_key_exists('customer_feedback', $validated) && trim((string) $validated['customer_feedback']) !== '';

        if (! $hasRating && ! $hasFeedback) {
            return back()->withErrors(['feedback' => 'Provide a rating and/or feedback before submitting.']);
        }

        if ($hasRating) {
            $booking->customer_rating = (int) $validated['customer_rating'];
        }

        if ($hasFeedback) {
            $booking->customer_feedback = trim((string) $validated['customer_feedback']);
        }

        $booking->save();

        return back()->with('status', 'Thanks! Your feedback has been submitted.');
    }

    public function respondEmergencyDecision(Request $request, Booking $booking): RedirectResponse
    {
        $booking = $this->resolveOwnedBooking($request, $booking);

        if ((string) $booking->status !== 'queued') {
            return back()->withErrors(['decision' => 'Only queued bookings can receive emergency decisions.']);
        }

        if (! (bool) ($booking->requires_customer_decision ?? false)) {
            return back()->withErrors(['decision' => 'No pending emergency decision is required for this booking.']);
        }

        $validated = $request->validate([
            'decision_action' => ['required', 'in:accept_reassign,reschedule,cancel'],
        ]);

        $action = (string) $validated['decision_action'];

        if ($action === 'accept_reassign') {
            $replacementBarberId = (int) ($booking->proposed_replacement_barber_id ?? 0);

            if ($replacementBarberId <= 0) {
                return back()->withErrors(['decision' => 'No replacement barber is currently proposed. Please choose reschedule or cancel, or wait for a new offer.']);
            }

            $replacementBarber = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', (string) $booking->tenant_id)
                ->where('branch_id', (int) $booking->branch_id)
                ->role('Barber')
                ->find($replacementBarberId);

            if (! $replacementBarber) {
                return back()->withErrors(['decision' => 'Proposed replacement barber is no longer available. Please choose reschedule or cancel.']);
            }

            $booking->barber_id = $replacementBarber->id;
            $booking->staff_id = $replacementBarber->id;
            $booking->notes = trim((string) ($booking->notes ?? '')."\nCustomer accepted emergency reassignment to {$replacementBarber->name} on ".now()->format('Y-m-d H:i'));
            $booking->requires_customer_decision = false;
            $booking->proposed_replacement_barber_id = null;
            $booking->customer_decision_due_at = null;
            $booking->emergency_reason = null;
            $booking->save();

            return back()->with('status', "Thanks for confirming. Your booking has been reassigned to {$replacementBarber->name}.");
        }

        if ($action === 'reschedule') {
            $booking->status = 'cancelled';
            $booking->notes = trim((string) ($booking->notes ?? '')."\nCustomer requested emergency reschedule on ".now()->format('Y-m-d H:i'));
            $booking->requires_customer_decision = false;
            $booking->proposed_replacement_barber_id = null;
            $booking->customer_decision_due_at = null;
            $booking->save();

            return redirect()
                ->route('customer.bookings.create')
                ->with('status', 'Your previous booking was closed for rescheduling. Please pick a new time now.');
        }

        $booking->status = 'cancelled';
        $booking->notes = trim((string) ($booking->notes ?? '')."\nCustomer cancelled due to barber emergency on ".now()->format('Y-m-d H:i'));
        $booking->requires_customer_decision = false;
        $booking->proposed_replacement_barber_id = null;
        $booking->customer_decision_due_at = null;
        $booking->save();

        return back()->with('status', 'Booking cancelled as requested.');
    }

    private function resolveOwnedBooking(Request $request, Booking $booking): Booking
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');

        $ownedBooking = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->where('id', $booking->id)
            ->first();

        abort_if($ownedBooking === null, 403);

        return $ownedBooking;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value:string, label:string}>
     */
    private function availableSlotsForDate(string $tenantId, int $barberId, string $date, int $serviceId = 0, int $excludeBookingId = 0): \Illuminate\Support\Collection
    {
        if ($tenantId === '' || $barberId <= 0) {
            return collect();
        }

        $serviceDurationMinutes = $this->resolveServiceDurationMinutes($tenantId, $serviceId);

        try {
            $dateValue = CarbonImmutable::parse($date)->startOfDay();
        } catch (\Throwable) {
            return collect();
        }

        $dayOfWeek = (int) $dateValue->dayOfWeek;

        $override = ScheduleOverride::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barberId)
            ->whereDate('schedule_date', $dateValue->toDateString())
            ->first(['is_working', 'start_time', 'end_time']);

        if ($override) {
            if (! $override->is_working) {
                return collect();
            }

            $schedules = collect();

            if (! empty($override->start_time) && ! empty($override->end_time)) {
                $schedules->push((object) [
                    'start_time' => $override->start_time,
                    'end_time' => $override->end_time,
                ]);
            }
        } else {
            $schedules = Schedule::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('barber_id', $barberId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working', true)
                ->orderBy('start_time')
                ->get(['start_time', 'end_time']);
        }

        if ($schedules->isEmpty()) {
            return collect();
        }

        $occupiedQuery = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barberId)
            ->whereDate('appointment_datetime', $dateValue->toDateString())
            ->whereIn('status', ['queued', 'confirmed', 'arrived', 'late', 'in_progress', 'completed']);

        if ($excludeBookingId > 0) {
            $occupiedQuery->where('id', '!=', $excludeBookingId);
        }

        $occupiedIntervals = $occupiedQuery
            ->with(['service:id,duration_minutes'])
            ->get(['appointment_datetime', 'service_id'])
            ->map(function (Booking $booking): array {
                $start = CarbonImmutable::parse((string) $booking->appointment_datetime);
                $duration = (int) ($booking->service?->duration_minutes ?? self::DEFAULT_SLOT_INTERVAL_MINUTES);
                $duration = $duration > 0 ? $duration : self::DEFAULT_SLOT_INTERVAL_MINUTES;

                return [
                    'start' => $start,
                    'end' => $start->addMinutes($duration),
                ];
            });

        $slots = collect();

        foreach ($schedules as $schedule) {
            $cursor = CarbonImmutable::parse($dateValue->toDateString().' '.$schedule->start_time);
            $end = CarbonImmutable::parse($dateValue->toDateString().' '.$schedule->end_time);

            while ($cursor->addMinutes($serviceDurationMinutes)->lte($end)) {
                $value = $cursor->format('H:i');
                $candidateEnd = $cursor->addMinutes($serviceDurationMinutes);

                $hasConflict = $occupiedIntervals->contains(function (array $interval) use ($cursor, $candidateEnd): bool {
                    return $cursor->lt($interval['end']) && $candidateEnd->gt($interval['start']);
                });

                if (! $hasConflict && $cursor->greaterThan(now())) {
                    $slots->push([
                        'value' => $value,
                        'label' => $cursor->format('g:i A'),
                    ]);
                }

                $cursor = $cursor->addMinutes(self::SLOT_STEP_MINUTES);
            }
        }

        return $slots->unique('value')->values();
    }

    private function resolveServiceDurationMinutes(string $tenantId, int $serviceId): int
    {
        if ($tenantId === '' || $serviceId <= 0) {
            return self::DEFAULT_SLOT_INTERVAL_MINUTES;
        }

        $service = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($serviceId, ['id', 'duration_minutes']);

        $duration = (int) ($service?->duration_minutes ?? self::DEFAULT_SLOT_INTERVAL_MINUTES);

        return $duration > 0 ? $duration : self::DEFAULT_SLOT_INTERVAL_MINUTES;
    }
}
