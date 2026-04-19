<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Booking;
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
    public function index(Request $request): View
    {
        $customer = $request->user();
        $tenantId = (string) ($customer->tenant_id ?? '');
        $bookingSortColumn = Schema::hasColumn('bookings', 'booked_at') ? 'booked_at' : 'created_at';

        $bookings = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->with(['service', 'staff'])
            ->latest($bookingSortColumn)
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

        $branches = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

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
            ->get(['id', 'name', 'branch_id']);

        $routeServiceId = (int) ($request->route('service') ?? 0);
        $selectedServiceId = $routeServiceId > 0 ? $routeServiceId : (int) old('service_id', 0);

        return view('customer.booking.create', [
            'branches' => $branches,
            'services' => $services,
            'barbers' => $barbers,
            'selectedServiceId' => $selectedServiceId,
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

        $branch = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($validated['branch_id']);

        $staffId = $validated['staff_id'] ?? null;

        if ($staffId !== null) {
            User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branch->id)
                ->role('Barber')
                ->findOrFail($staffId);
        }

        $price = (float) ($service->base_price ?? $service->price ?? 0);

        // Find a default barber if one isn't explicitly chosen
        $barberUsr = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branch->id)
            ->role('Barber')
            ->first();

        if (! $staffId && ! $barberUsr) {
            return back()->withErrors(['staff_id' => 'No barber is assigned to the selected branch yet.'])->withInput();
        }

        $barberId = $staffId ?? $barberUsr->id;

        try {
            $booking = DB::transaction(function () use ($tenantId, $customer, $service, $branch, $staffId, $barberId, $price, $validated): Booking {
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
                    'appointment_datetime' => now(),
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

        return redirect()->route('booking.index')
            ->with('status', "You're in! Booking #{$booking->id} confirmed.");
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
}
