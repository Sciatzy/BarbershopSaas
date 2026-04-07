<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PointsLedger;

class PointsController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $balance = $user->points_balance ?? 0;

        $ledger  = class_exists(PointsLedger::class) ? PointsLedger::where('customer_id', $user->id)
            ->with(['booking.service']) // Eager load depending on relations
            ->latest()
            ->paginate(15) : collect();

        // Milestones: define redemption thresholds
        $milestones = [
            ['points' => 300,  'reward' => 'Free Beard Lineup'],
            ['points' => 500,  'reward' => 'Free Classic Cut'],
            ['points' => 800,  'reward' => 'Free Skin Fade'],
            ['points' => 1200, 'reward' => 'Free Cut + Beard Combo'],
        ];

        return view('customer.points.index', compact('balance', 'ledger', 'milestones'));
    }
}