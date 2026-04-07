<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasRole('Platform Admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('Barbershop Admin')) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->hasRole('Branch Manager')) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->hasRole('Barber')) {
            return redirect()->route('barber.dashboard');
        }

        if ($user->hasRole('Customer')) {
            return redirect()->route('customer.dashboard');
        }

        // Fallback for tenant-scoped users whose role data is temporarily missing/misaligned.
        if (! empty($user->tenant_id)) {
            return redirect()->route('manager.dashboard');
        }

        return view('dashboard', [
            'roles' => $user->getRoleNames(),
        ]);
    }
}
