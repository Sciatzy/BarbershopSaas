<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PointsLedger;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $balance = $user->points_balance ?? 0;

        $ledger = PointsLedger::query()
            ->where('customer_id', $user->id)
            ->with(['booking.service'])
            ->latest()
            ->paginate(15);

        // Milestones: define redemption thresholds
        $milestones = [
            ['points' => 300,  'reward' => 'Free Beard Lineup'],
            ['points' => 500,  'reward' => 'Free Classic Cut'],
            ['points' => 800,  'reward' => 'Free Skin Fade'],
            ['points' => 1200, 'reward' => 'Free Cut + Beard Combo'],
        ];

        return view('customer.points.index', compact('balance', 'ledger', 'milestones'));
    }

    public function redeem(Request $request, PointsService $pointsService): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
            'reward' => ['nullable', 'string', 'max:255'],
        ]);

        $points = (int) $validated['points'];
        $reward = (string) ($validated['reward'] ?? '');

        $notes = $reward !== '' ? ('Redeemed reward: '.$reward) : 'Redeemed points';

        $success = $pointsService->redeemPoints($user, $points, null, $notes);

        if (! $success) {
            return back()->with('points_error', 'Unable to redeem points. Please check your balance.');
        }

        return back()->with('points_status', 'Redeemed '.$points.' points successfully.');
    }
}