<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BarberCashout;
use App\Services\BarberCashoutService;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarberCashoutController extends Controller
{
    public function approve(Request $request, BarberCashout $cashout, BarberCashoutService $cashoutService, PointsService $pointsService): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $success = $cashoutService->approveCashout($cashout, $user, $pointsService);

        if (! $success) {
            return back()->with('cashout_error', 'Unable to approve cashout.');
        }

        return back()->with('cashout_status', 'Cashout approved. Points deducted from barber balance.');
    }

    public function reject(Request $request, BarberCashout $cashout, BarberCashoutService $cashoutService): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $success = $cashoutService->rejectCashout($cashout, $user, $validated['reason'] ?? null);

        if (! $success) {
            return back()->with('cashout_error', 'Unable to reject cashout.');
        }

        return back()->with('cashout_status', 'Cashout rejected.');
    }

    public function paid(Request $request, BarberCashout $cashout, BarberCashoutService $cashoutService): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Branch Manager')) {
            abort(403);
        }

        $success = $cashoutService->markPaid($cashout, $user);

        if (! $success) {
            return back()->with('cashout_error', 'Unable to mark cashout as paid.');
        }

        return back()->with('cashout_status', 'Cashout marked as paid.');
    }
}
