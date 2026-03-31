<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BookingController extends Controller
{
    private const SLOT_INTERVAL_MINUTES = 30;

    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        $selectedDate = (string) $request->query('date', now()->toDateString());
        $selectedBranchId = (int) $request->query('branch_id', 0);
        $selectedServiceId = (int) $request->query('service_id', 0);
        $selectedBarberId = (int) $request->query('barber_id', 0);

        $branches = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        $singleBranch = $branches->count() === 1 ? $branches->first() : null;

        if ($selectedBranchId <= 0 && $singleBranch !== null) {
            $selectedBranchId = (int) $singleBranch->id;
        }

        if ($selectedBranchId > 0 && ! $branches->contains(fn ($branch) => (int) $branch->id === $selectedBranchId)) {
            $selectedBranchId = 0;
        }

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'price', 'duration_minutes']);

        if ($selectedServiceId > 0 && ! $services->contains(fn ($service) => (int) $service->id === $selectedServiceId)) {
            $selectedServiceId = 0;
        }

        $barbersQuery = User::query()
            ->withoutGlobalScopes()
            ->role('Barber')
            ->where('tenant_id', $tenantId)
            ->orderBy('name');

        if ($selectedBranchId > 0) {
            $barbersQuery->where('branch_id', $selectedBranchId);
        } elseif ($branches->count() > 1) {
            $barbersQuery->whereRaw('1 = 0');
        }

        $barbers = $barbersQuery->get(['id', 'name', 'branch_id']);

        if ($selectedBarberId > 0 && ! $barbers->contains(fn ($barber) => (int) $barber->id === $selectedBarberId)) {
            $selectedBarberId = 0;
        }

        $availableSlots = $this->availableSlotsForDate(
            $tenantId,
            $selectedBarberId,
            $selectedDate,
        );

        return view('booking.index', [
            'branches' => $branches,
            'services' => $services,
            'barbers' => $barbers,
            'availableSlots' => $availableSlots,
            'selectedDate' => $selectedDate,
            'selectedBranchId' => $selectedBranchId,
            'selectedServiceId' => $selectedServiceId,
            'selectedBarberId' => $selectedBarberId,
            'singleBranch' => $singleBranch,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'barber_id' => ['required', 'integer'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        $branch = Branch::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->findOrFail($validated['branch_id']);
        Service::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->findOrFail($validated['service_id']);
        User::query()
            ->withoutGlobalScopes()
            ->role('Barber')
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->findOrFail($validated['barber_id']);

        $appointmentDateTime = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time']
        );

        $appointment = new Appointment();
        $appointment->tenant_id = $tenantId;
        $appointment->branch_id = $validated['branch_id'];
        $appointment->customer_id = $user->id;
        $appointment->barber_id = $validated['barber_id'];
        $appointment->service_id = $validated['service_id'];
        $appointment->appointment_datetime = $appointmentDateTime->toDateTimeString();
        $appointment->source = 'online';
        $appointment->status = 'pending';
        $appointment->is_on_time = false;
        $appointment->customer_rating = null;
        $appointment->work_notes = null;
        $appointment->save();

        return redirect()->route('booking.index', [
            'branch_id' => $validated['branch_id'],
            'service_id' => $validated['service_id'],
            'barber_id' => $validated['barber_id'],
            'date' => $validated['appointment_date'],
        ])->with('status', 'Appointment request submitted successfully.');
    }

    /**
     * @return Collection<int, array{value:string, label:string}>
     */
    private function availableSlotsForDate(string $tenantId, int $barberId, string $date): Collection
    {
        if ($tenantId === '' || $barberId <= 0) {
            return collect();
        }

        try {
            $dateValue = CarbonImmutable::parse($date)->startOfDay();
        } catch (\Throwable) {
            return collect();
        }

        $dayOfWeek = (int) $dateValue->dayOfWeek;

        $schedules = Schedule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barberId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working', true)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        if ($schedules->isEmpty()) {
            return collect();
        }

        $occupiedSlots = Appointment::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $barberId)
            ->whereDate('appointment_datetime', $dateValue->toDateString())
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->pluck('appointment_datetime')
            ->map(fn ($value) => CarbonImmutable::parse((string) $value)->format('H:i'))
            ->flip();

        $slots = collect();

        foreach ($schedules as $schedule) {
            $cursor = CarbonImmutable::parse($dateValue->toDateString().' '.$schedule->start_time);
            $end = CarbonImmutable::parse($dateValue->toDateString().' '.$schedule->end_time);

            while ($cursor->lt($end)) {
                $value = $cursor->format('H:i');

                if (! $occupiedSlots->has($value)) {
                    $slots->push([
                        'value' => $value,
                        'label' => $cursor->format('g:i A'),
                    ]);
                }

                $cursor = $cursor->addMinutes(self::SLOT_INTERVAL_MINUTES);
            }
        }

        return $slots->unique('value')->values();
    }
}
