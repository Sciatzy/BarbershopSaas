<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PointsLedger;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isCustomer = $user->hasRole('Customer');

        $tenant = null;

        if ($isCustomer) {
            $tenant = $user->tenant;
        } elseif (! empty($user->tenant_id)) {
            $tenant = Tenant::withoutGlobalScopes()->find($user->tenant_id);
        } else {
            $requestedTenantId = (string) $request->query('tenant', '');

            if ($requestedTenantId !== '') {
                $tenant = Tenant::withoutGlobalScopes()->find($requestedTenantId);
            }

            if (! $tenant) {
                $resolvedTenant = $request->attributes->get('currentTenant');

                if ($resolvedTenant instanceof Tenant) {
                    $tenant = $resolvedTenant;
                }
            }

            if (! $tenant) {
                $tenant = Tenant::withoutGlobalScopes()->orderBy('name')->first();
            }
        }

        $tenantId = (string) ($tenant->id ?? '');

        if ($isCustomer) {
            $recentBookings = Booking::where('customer_id', $user->id)
                ->with(['service', 'staff'])
                ->latest('booked_at')
                ->take(5)
                ->get();

            $activeBooking = Booking::where('customer_id', $user->id)
                ->whereIn('status', ['queued', 'in_progress'])
                ->with(['service', 'staff'])
                ->latest('booked_at')
                ->first();

            $pointsBalance = $user->points_balance ?? 0;
            $totalEarned = class_exists(PointsLedger::class) ? PointsLedger::where('customer_id', $user->id)
                ->where('type', 'earn')->sum('points') : 0;
            $totalRedeemed = class_exists(PointsLedger::class) ? abs(PointsLedger::where('customer_id', $user->id)
                ->where('type', 'redeem')->sum('points')) : 0;

            $totalVisits = Booking::where('customer_id', $user->id)
                ->where('status', 'completed')->count();

            $rank = User::where('tenant_id', $tenantId !== '' ? $tenantId : null)
                ->whereHas('roles', fn ($q) => $q->where('name', 'Customer'))
                ->where('points_balance', '>', $pointsBalance)
                ->count() + 1;
        } else {
            $recentBookings = Booking::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->with(['service', 'staff'])
                ->latest('booked_at')
                ->take(5)
                ->get();

            $activeBooking = Booking::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['queued', 'in_progress'])
                ->with(['service', 'staff'])
                ->latest('booked_at')
                ->first();

            $pointsBalance = 0;
            $totalEarned = 0;
            $totalRedeemed = 0;
            $totalVisits = Booking::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->count();
            $rank = 0;
        }

        $services = Service::where('tenant_id', $tenantId !== '' ? $tenantId : null)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('customer.dashboard', compact(
            'user', 'tenant', 'recentBookings', 'activeBooking',
            'pointsBalance', 'totalEarned', 'totalRedeemed',
            'totalVisits', 'services', 'rank'
        ));
    }
}
