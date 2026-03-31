<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return view('barber.dashboard', [
                'scheduleToday' => collect(),
                'appointmentsToday' => collect(),
                'totalPoints' => 0,
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
        ]);
    }
}
