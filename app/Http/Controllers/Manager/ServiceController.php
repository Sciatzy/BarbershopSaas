<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (string) ($user->tenant_id ?? '');

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('manager.services.index', [
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (string) ($request->user()->tenant_id ?? '');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Service::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'price' => $validated['base_price'],
            'duration_min' => $validated['duration_minutes'],
            'duration_minutes' => $validated['duration_minutes'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'type' => ((float) $validated['base_price']) >= 350 ? 'premium' : 'standard',
        ]);

        return redirect()->route('manager.services.index')->with('billing_status', 'Service added successfully.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $tenantId = (string) ($request->user()->tenant_id ?? '');

        abort_if((string) ($service->tenant_id ?? '') !== $tenantId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'price' => $validated['base_price'],
            'duration_min' => $validated['duration_minutes'],
            'duration_minutes' => $validated['duration_minutes'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'type' => ((float) $validated['base_price']) >= 350 ? 'premium' : 'standard',
        ])->save();

        return redirect()->route('manager.services.index')->with('billing_status', 'Service updated successfully.');
    }
}
