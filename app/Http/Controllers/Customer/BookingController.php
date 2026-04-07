<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');

        $bookings = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->with(['service', 'staff'])
            ->latest('booked_at')
            ->latest('created_at')
            ->get();

        return view('customer.booking.index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $barbers = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Barber')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('customer.booking.create', [
            'services' => $services,
            'barbers' => $barbers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');

        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
            'staff_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $service = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail($validated['service_id']);

        $staffId = $validated['staff_id'] ?? null;

        if ($staffId !== null) {
            User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->role('Barber')
                ->findOrFail($staffId);
        }

        $price = (float) ($service->base_price ?? $service->price ?? 0);

        // Default to the tenant's first branch since we aren't selecting branches yet
        $branch = \App\Models\Branch::where('tenant_id', $tenantId)->first();
        if (! $branch) {
            return back()->withErrors(['error' => 'No branch setup for this shop yet.']);
        }

        // Find a default barber if one isn't explicitly chosen
        $barberUsr = User::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->role('Barber')->first();
        $barberId = $staffId ?? ($barberUsr ? $barberUsr->id : $customer->id);

        $booking = Booking::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staffId,
            'barber_id' => $barberId,
            'total_price' => $price,
            'status' => 'queued',
            'booked_at' => now(),
            'appointment_datetime' => now(),
            'notes' => $validated['notes'] ?? null,
            'source' => 'online',
            'created_by' => $customer->id,
            'is_on_time' => false,
        ]);

        return redirect()->route('booking.index')
            ->with('status', "You're in! Booking #{$booking->id} confirmed.");
    }
}
