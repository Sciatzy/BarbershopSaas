<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (string) ($request->user()->tenant_id ?? '');

        $bookings = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['queued', 'in_progress'])
            ->with(['customer', 'service'])
            ->orderBy('booked_at')
            ->orderBy('created_at')
            ->get();

        return view('manager.queue.index', [
            'bookings' => $bookings,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking, PointsService $pointsService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,completed,cancelled'],
        ]);

        $tenantId = (string) ($request->user()->tenant_id ?? '');

        if ((string) $booking->tenant_id !== $tenantId) {
            abort(403);
        }

        $oldStatus = (string) $booking->status;
        $newStatus = (string) $validated['status'];
        $pointsAwarded = 0;

        $booking->status = $newStatus;

        if ($newStatus === 'completed') {
            $booking->setAttribute('completed_at', now());
        }

        $booking->save();

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $pointsAwarded = $pointsService->awardPoints($booking);
        }

        if ($newStatus === 'completed') {
            $customerName = $booking->customer?->name ?? 'Customer';

            return redirect()->back()->with(
                'status',
                "Booking #{$booking->id} completed. {$customerName} earned {$pointsAwarded} points!"
            );
        }

        return redirect()->back()->with('status', "Booking #{$booking->id} marked as {$newStatus}.");
    }
}
