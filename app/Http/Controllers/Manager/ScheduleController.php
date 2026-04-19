<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
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

        return view('manager.schedules.index', [
            'barbers' => $barbers,
            'schedules' => $schedules,
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
