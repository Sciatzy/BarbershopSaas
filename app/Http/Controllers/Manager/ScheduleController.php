<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\ScheduleOverride;
use App\Models\User;
use App\Services\TenantLifecycleNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private TenantLifecycleNotifier $notifier) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        $barbers = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->orderBy('name')
            ->get(['id', 'name']);

        $schedules = Schedule::query()
            ->withoutGlobalScopes()
            ->join('users as barbers', 'barbers.id', '=', 'schedules.barber_id')
            ->where('schedules.tenant_id', $tenantId)
            ->where('barbers.branch_id', $branchId)
            ->orderBy('barbers.name')
            ->orderBy('schedules.day_of_week')
            ->orderBy('schedules.start_time')
            ->get([
                'schedules.id',
                'schedules.barber_id',
                'schedules.day_of_week',
                'schedules.start_time',
                'schedules.end_time',
                'schedules.is_working',
                'barbers.name as barber_name',
            ]);

        $overrides = ScheduleOverride::query()
            ->withoutGlobalScopes()
            ->join('users as barbers', 'barbers.id', '=', 'schedule_overrides.barber_id')
            ->where('schedule_overrides.tenant_id', $tenantId)
            ->where('barbers.branch_id', $branchId)
            ->whereDate('schedule_overrides.schedule_date', '>=', now()->toDateString())
            ->orderBy('schedule_overrides.schedule_date')
            ->orderBy('barbers.name')
            ->limit(120)
            ->get([
                'schedule_overrides.id',
                'schedule_overrides.barber_id',
                'schedule_overrides.schedule_date',
                'schedule_overrides.is_working',
                'schedule_overrides.start_time',
                'schedule_overrides.end_time',
                'schedule_overrides.notes',
                'barbers.name as barber_name',
            ]);

        $selectedEmergencyBarberId = (int) $request->query('emergency_barber_id', 0);
        $selectedEmergencyDate = trim((string) $request->query('emergency_date', ''));

        $impactedBookings = collect();
        $replacementBarbers = collect();

        if ($selectedEmergencyBarberId > 0 && $selectedEmergencyDate !== '') {
            $selectedBarber = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->role('Barber')
                ->find($selectedEmergencyBarberId);

            if ($selectedBarber) {
                $impactedBookings = Booking::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->where('barber_id', $selectedBarber->id)
                    ->whereIn('status', ['queued'])
                    ->whereDate('appointment_datetime', $selectedEmergencyDate)
                    ->with(['customer', 'service', 'barber', 'staff'])
                    ->orderBy('appointment_datetime')
                    ->get();

                $replacementBarbers = User::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->role('Barber')
                    ->where('id', '!=', $selectedBarber->id)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        }

        return view('manager.schedules.index', [
            'barbers' => $barbers,
            'schedules' => $schedules,
            'overrides' => $overrides,
            'selectedEmergencyBarberId' => $selectedEmergencyBarberId,
            'selectedEmergencyDate' => $selectedEmergencyDate,
            'impactedBookings' => $impactedBookings,
            'replacementBarbers' => $replacementBarbers,
            'weekdayLabels' => $this->weekdayLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        $validated = $request->validate([
            'barber_id' => ['required', 'integer'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_working' => ['nullable', 'boolean'],
        ]);

        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->findOrFail((int) $validated['barber_id']);

        $schedule = Schedule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barber->id)
            ->where('day_of_week', (int) $validated['day_of_week'])
            ->first();

        if (! $schedule) {
            $schedule = new Schedule();
            $schedule->tenant_id = $tenantId;
            $schedule->barber_id = $barber->id;
            $schedule->day_of_week = (int) $validated['day_of_week'];
        }

        $schedule->start_time = $validated['start_time'];
        $schedule->end_time = $validated['end_time'];
        $schedule->is_working = (bool) ($validated['is_working'] ?? true);
        $schedule->save();

        return redirect()->route('manager.schedules.index')->with('billing_status', 'Barber schedule saved successfully.');
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        abort_if((string) ($schedule->tenant_id ?? '') !== $tenantId, 403);

        $barberInBranch = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->where('id', $schedule->barber_id)
            ->exists();

        abort_if(! $barberInBranch, 403);

        $schedule->delete();

        return redirect()->route('manager.schedules.index')->with('billing_status', 'Schedule removed successfully.');
    }

    public function storeOverride(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        $validated = $request->validate([
            'override_barber_id' => ['required', 'integer'],
            'schedule_date' => ['required', 'date', 'after_or_equal:today'],
            'override_type' => ['required', 'in:off,custom'],
            'override_start_time' => ['nullable', 'date_format:H:i', 'required_if:override_type,custom'],
            'override_end_time' => ['nullable', 'date_format:H:i', 'required_if:override_type,custom', 'after:override_start_time'],
            'override_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->findOrFail((int) $validated['override_barber_id']);

        $scheduleDate = Carbon::parse((string) $validated['schedule_date'])->toDateString();

        $override = ScheduleOverride::query()
            ->withoutGlobalScopes()
            ->firstOrNew([
                'tenant_id' => $tenantId,
                'barber_id' => $barber->id,
                'schedule_date' => $scheduleDate,
            ]);

        $isWorking = (string) $validated['override_type'] === 'custom';

        $override->is_working = $isWorking;
        $override->start_time = $isWorking ? $validated['override_start_time'] : null;
        $override->end_time = $isWorking ? $validated['override_end_time'] : null;
        $override->notes = (string) ($validated['override_notes'] ?? '') !== ''
            ? (string) $validated['override_notes']
            : null;
        $override->save();

        return redirect()
            ->route('manager.schedules.index')
            ->with('billing_status', 'Date override saved successfully.');
    }

    public function destroyOverride(Request $request, ScheduleOverride $override): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        abort_if((string) ($override->tenant_id ?? '') !== $tenantId, 403);

        $barberInBranch = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->where('id', $override->barber_id)
            ->exists();

        abort_if(! $barberInBranch, 403);

        $override->delete();

        return redirect()->route('manager.schedules.index')->with('billing_status', 'Date override removed successfully.');
    }

    public function storeEmergencyAbsence(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        $validated = $request->validate([
            'emergency_barber_id' => ['required', 'integer'],
            'emergency_date' => ['required', 'date', 'after_or_equal:today'],
            'emergency_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->findOrFail((int) $validated['emergency_barber_id']);

        $scheduleDate = Carbon::parse((string) $validated['emergency_date'])->toDateString();

        $override = ScheduleOverride::query()
            ->withoutGlobalScopes()
            ->firstOrNew([
                'tenant_id' => $tenantId,
                'barber_id' => $barber->id,
                'schedule_date' => $scheduleDate,
            ]);

        $override->is_working = false;
        $override->start_time = null;
        $override->end_time = null;
        $override->notes = trim((string) ($validated['emergency_reason'] ?? '')) !== ''
            ? trim((string) $validated['emergency_reason'])
            : 'Emergency absence';
        $override->save();

        return redirect()
            ->route('manager.schedules.index', [
                'emergency_barber_id' => $barber->id,
                'emergency_date' => $scheduleDate,
            ])
            ->with('billing_status', 'Emergency off-day set. Request customer decisions for impacted bookings below.');
    }

    public function requestEmergencyDecision(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        $validated = $request->validate([
            'proposed_replacement_barber_id' => ['nullable', 'integer'],
            'emergency_barber_id' => ['nullable', 'integer'],
            'emergency_date' => ['nullable', 'date'],
            'emergency_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $managedBooking = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('id', $booking->id)
            ->first();

        abort_if(! $managedBooking, 403);

        if (! in_array((string) $managedBooking->status, ['queued'], true)) {
            return back()->with('billing_status', 'Only queued bookings can be marked for customer decision.');
        }

        $proposedReplacementId = null;

        if (! empty($validated['proposed_replacement_barber_id'])) {
            $proposedReplacement = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->role('Barber')
                ->findOrFail((int) $validated['proposed_replacement_barber_id']);

            $proposedReplacementId = (int) $proposedReplacement->id;
        }

        $managedBooking->requires_customer_decision = true;
        $managedBooking->proposed_replacement_barber_id = $proposedReplacementId;
        $managedBooking->customer_decision_due_at = Carbon::now()->addHours(12);
        $managedBooking->emergency_reason = trim((string) ($validated['emergency_reason'] ?? '')) !== ''
            ? trim((string) $validated['emergency_reason'])
            : null;
        $managedBooking->notes = trim((string) ($managedBooking->notes ?? '')."\nEmergency customer decision requested on ".now()->format('Y-m-d H:i'));
        $managedBooking->save();

        $this->notifyCustomerForEmergencyDecision($managedBooking);

        $redirectParams = [];

        if (! empty($validated['emergency_barber_id'])) {
            $redirectParams['emergency_barber_id'] = (int) $validated['emergency_barber_id'];
        }

        if (! empty($validated['emergency_date'])) {
            $redirectParams['emergency_date'] = (string) $validated['emergency_date'];
        }

        return redirect()
            ->route('manager.schedules.index', $redirectParams)
            ->with('billing_status', "Customer decision requested for booking #{$managedBooking->id}.");
    }

    public function requestAllEmergencyDecisions(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        $branchId = (int) ($user->branch_id ?? 0);

        $validated = $request->validate([
            'emergency_barber_id' => ['required', 'integer'],
            'emergency_date' => ['required', 'date'],
            'proposed_replacement_barber_id' => ['nullable', 'integer'],
            'emergency_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $absentBarber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->role('Barber')
            ->findOrFail((int) $validated['emergency_barber_id']);

        $proposedReplacementId = null;

        if (! empty($validated['proposed_replacement_barber_id'])) {
            $proposedReplacement = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->role('Barber')
                ->where('id', '!=', $absentBarber->id)
                ->findOrFail((int) $validated['proposed_replacement_barber_id']);

            $proposedReplacementId = (int) $proposedReplacement->id;
        }

        $emergencyDate = Carbon::parse((string) $validated['emergency_date'])->toDateString();

        $impactedBookings = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('barber_id', $absentBarber->id)
            ->whereIn('status', ['queued'])
            ->whereDate('appointment_datetime', $emergencyDate)
            ->orderBy('appointment_datetime')
            ->get();

        if ($impactedBookings->isEmpty()) {
            return redirect()
                ->route('manager.schedules.index', [
                    'emergency_barber_id' => $absentBarber->id,
                    'emergency_date' => $emergencyDate,
                ])
                ->with('billing_status', 'No queued bookings to reassign for the selected emergency date.');
        }

        $requestedCount = DB::transaction(function () use ($impactedBookings, $proposedReplacementId, $validated): int {
            $requested = 0;

            foreach ($impactedBookings as $booking) {
                /** @var Booking $booking */
                $booking->requires_customer_decision = true;
                $booking->proposed_replacement_barber_id = $proposedReplacementId;
                $booking->customer_decision_due_at = Carbon::now()->addHours(12);
                $booking->emergency_reason = trim((string) ($validated['emergency_reason'] ?? '')) !== ''
                    ? trim((string) $validated['emergency_reason'])
                    : null;
                $booking->notes = trim((string) ($booking->notes ?? '')."\nEmergency customer decision requested on ".now()->format('Y-m-d H:i'));
                $booking->save();

                $this->notifyCustomerForEmergencyDecision($booking);

                $requested++;
            }

            return $requested;
        });

        return redirect()
            ->route('manager.schedules.index', [
                'emergency_barber_id' => $absentBarber->id,
                'emergency_date' => $emergencyDate,
            ])
            ->with('billing_status', "Customer decision requests sent for {$requestedCount} impacted queued bookings.");
    }

    private function notifyCustomerForEmergencyDecision(Booking $booking): void
    {
        $customer = $booking->customer;

        if (! $customer) {
            return;
        }

        $barberName = (string) ($booking->barber?->name ?? 'your selected barber');
        $serviceName = (string) ($booking->service?->name ?? 'service');
        $appointmentTime = optional($booking->appointment_datetime)?->format('Y-m-d g:i A') ?? 'your scheduled time';
        $replacementName = (string) ($booking->proposedReplacement?->name ?? 'No replacement proposed yet');
        $decisionDue = optional($booking->customer_decision_due_at)?->format('Y-m-d g:i A') ?? 'as soon as possible';

        $this->notifier->notifyUserWithDetails(
            $customer,
            'Action Required: Your Barber Booking Needs a Decision',
            "Hi {$customer->name}, your scheduled barber is unavailable due to an emergency. Please choose whether to accept reassignment, reschedule, or cancel.",
            [
                'Booking ID' => (string) $booking->id,
                'Scheduled Time' => $appointmentTime,
                'Service' => $serviceName,
                'Original Barber' => $barberName,
                'Proposed Replacement' => $replacementName,
                'Decision Due By' => $decisionDue,
            ],
            'Sign in to your customer bookings page and submit your preferred option.'
        );
    }

    /**
     * @return array<int, string>
     */
    private function weekdayLabels(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }
}
