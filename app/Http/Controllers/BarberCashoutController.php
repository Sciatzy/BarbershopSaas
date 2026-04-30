<?php

namespace App\Http\Controllers;

use App\Services\BarberCashoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarberCashoutController extends Controller
{
    public function store(Request $request, BarberCashoutService $cashoutService): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Barber')) {
            abort(403);
        }

        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $success = $cashoutService->requestCashout($user, (int) $validated['points'], $validated['notes'] ?? null);

        if (! $success) {
            return back()->with('cashout_error', 'Unable to request cash bonus. Please check your points and tier selection.');
        }

        return back()->with('cashout_status', 'Cash bonus request submitted. Waiting for Branch Manager approval.');
    }
}
