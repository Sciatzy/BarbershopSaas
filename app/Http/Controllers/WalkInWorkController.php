<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class WalkInWorkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        if ($tenantId === '') {
            return back()->with('billing_error', 'No tenant is assigned to your account.');
        }

        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'barber_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'work_datetime' => ['required', 'date'],
            'is_on_time' => ['nullable', 'boolean'],
            'customer_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'work_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Branch::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($validated['branch_id']);

        User::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->role('Barber')
            ->findOrFail($validated['barber_id']);

        Service::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($validated['service_id']);

        $walkInCustomer = User::query()->firstOrCreate(
            ['email' => 'walkin+'.str_replace('-', '', $tenantId).'@local.barbershop'],
            [
                'tenant_id' => $tenantId,
                'name' => 'Walk-in Customer',
                'password' => Hash::make(bin2hex(random_bytes(16))),
            ]
        );

        if (! $walkInCustomer->hasRole('Customer')) {
            Role::findOrCreate('Customer', 'web');
            $walkInCustomer->assignRole('Customer');
        }

        $appointment = new Appointment();
        $appointment->tenant_id = $tenantId;
        $appointment->branch_id = $validated['branch_id'];
        $appointment->customer_id = $walkInCustomer->id;
        $appointment->barber_id = $validated['barber_id'];
        $appointment->service_id = $validated['service_id'];
        $appointment->appointment_datetime = Carbon::parse($validated['work_datetime'])->toDateTimeString();
        $appointment->source = 'walk_in';
        $appointment->status = 'completed';
        $appointment->is_on_time = (bool) ($validated['is_on_time'] ?? false);
        $appointment->customer_rating = $validated['customer_rating'] ?? null;
        $appointment->work_notes = $validated['work_notes'] ?? null;
        $appointment->save();

        return redirect()->route('manager.dashboard')->with(
            'billing_status',
            'Walk-in work recorded. Barber points were automatically updated based on service, punctuality, and rating.'
        );
    }
}
