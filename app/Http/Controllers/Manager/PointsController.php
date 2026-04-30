<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function adjustCustomer(Request $request, PointsService $pointsService): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! $actor->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($actor->tenant_id ?? '');

        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'delta' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User|null $customer */
        $customer = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($validated['customer_id']);

        if (! $customer || ! $customer->hasRole('Customer')) {
            return back()->with('points_error', 'Customer not found.');
        }

        $success = $pointsService->adjustCustomerPoints($customer, (int) $validated['delta'], $validated['notes'] ?? null);

        if (! $success) {
            return back()->with('points_error', 'Unable to adjust customer points (would go below zero).');
        }

        return back()->with('points_status', 'Customer points updated successfully.');
    }

    public function adjustBarber(Request $request, PointsService $pointsService): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! $actor->hasRole('Barbershop Admin')) {
            abort(403);
        }

        $tenantId = (string) ($actor->tenant_id ?? '');

        $validated = $request->validate([
            'barber_id' => ['required', 'integer'],
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User|null $barber */
        $barber = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($validated['barber_id']);

        if (! $barber || ! $barber->hasRole('Barber')) {
            return back()->with('points_error', 'Barber not found.');
        }

        $reason = (string) ($validated['reason'] ?? '');
        $reason = $reason !== '' ? $reason : 'Points adjustment';

        $success = $pointsService->adjustBarberPoints($barber, (int) $validated['delta'], $reason);

        if (! $success) {
            return back()->with('points_error', 'Unable to adjust barber points (would go below zero).');
        }

        return back()->with('points_status', 'Barber points updated successfully.');
    }
}
