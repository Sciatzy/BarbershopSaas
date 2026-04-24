<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PointTransaction;
use App\Models\Schedule;
use App\Models\Tenant;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');
        $tenant = $tenantId !== '' ? Tenant::query()->find($tenantId) : null;
        $canUpdateAppointmentStatus = $tenant?->dashboardFeatureEnabled('barber', 'update_appointment_status', true) ?? true;

        if ($tenantId === '') {
            return view('barber.dashboard', [
                'scheduleToday' => collect(),
                'appointmentsToday' => collect(),
                'totalPoints' => 0,
                'canUpdateAppointmentStatus' => true,
            ]);
        }

        $today = now();
        $todayDate = $today->toDateString();
        $dayOfWeek = (int) $today->dayOfWeek;

        $scheduleToday = Schedule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working', true)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

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
                'a.status',
                'customer.name as customer_name',
                's.name as service_name',
            ]);

        $totalPoints = PointTransaction::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('barber_id', $user->id)
            ->sum('points_awarded');

        return view('barber.dashboard', [
            'scheduleToday' => $scheduleToday,
            'appointmentsToday' => $appointmentsToday,
            'totalPoints' => $totalPoints,
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
            'status' => ['required', 'in:in_progress,completed'],
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

        if ($oldStatus === 'queued' && $newStatus === 'completed') {
            return back()->withErrors(['status' => 'Move booking to in progress before completing it.']);
        }

        if ($oldStatus === 'in_progress' && $newStatus === 'in_progress') {
            return back()->with('status', "Booking #{$booking->id} is already in progress.");
        }

        $booking->status = $newStatus;

        if ($newStatus === 'completed') {
            $booking->setAttribute('completed_at', now());
        }

        $booking->save();

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $pointsAwarded = $pointsService->awardPoints($booking);

            return back()->with('status', "Booking #{$booking->id} completed. Customer earned {$pointsAwarded} points.");
        }

        return back()->with('status', "Booking #{$booking->id} marked as {$newStatus}.");
    }
}
