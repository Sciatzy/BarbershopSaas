<?php

namespace App\Http\Controllers;

use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarberPointsController extends Controller
{
    public function redeem(Request $request, PointsService $pointsService): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barber')) {
            abort(403);
        }

        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $points = (int) $validated['points'];
        $reason = (string) ($validated['reason'] ?? '');

        $success = $pointsService->redeemBarberPoints($user, $points, $reason !== '' ? $reason : null);

        if (! $success) {
            return back()->with('status', 'Unable to redeem points. Please check your balance.');
        }

        return back()->with('status', 'Redeemed '.$points.' points successfully.');
    }
}
