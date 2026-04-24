<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        $services = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $archivedServices = Service::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->orderBy('name')
            ->get();

        return view('manager.services.index', [
            'services' => $services,
            'archivedServices' => $archivedServices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['standard', 'premium'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $resolvedType = (string) ($validated['type'] ?? (((float) $validated['base_price']) >= 350 ? 'premium' : 'standard'));

        Service::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'price' => $validated['base_price'],
            'duration_min' => $validated['duration_minutes'],
            'duration_minutes' => $validated['duration_minutes'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'archived_at' => null,
            'type' => $resolvedType,
        ]);

        return redirect()->route('manager.services.index')->with('billing_status', 'Service added successfully.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        abort_if((string) ($service->tenant_id ?? '') !== $tenantId, 403);

        if ($service->archived_at !== null) {
            return redirect()->route('manager.services.index')
                ->with('billing_error', 'Archived services cannot be edited. Restore the service first.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['standard', 'premium'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $resolvedType = (string) ($validated['type'] ?? (((float) $validated['base_price']) >= 350 ? 'premium' : 'standard'));

        $service->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'price' => $validated['base_price'],
            'duration_min' => $validated['duration_minutes'],
            'duration_minutes' => $validated['duration_minutes'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'type' => $resolvedType,
        ])->save();

        return redirect()->route('manager.services.index')->with('billing_status', 'Service updated successfully.');
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        abort_if((string) ($service->tenant_id ?? '') !== $tenantId, 403);

        if ($service->archived_at !== null) {
            return redirect()->route('manager.services.index')->with('billing_status', 'Service is already archived.');
        }

        $service->forceFill([
            'is_active' => false,
            'archived_at' => Carbon::now(),
        ])->save();

        return redirect()->route('manager.services.index')->with('billing_status', 'Service archived successfully.');
    }

    public function restore(Request $request, Service $service): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            abort(403);
        }

        $tenantId = (string) ($user->tenant_id ?? '');

        abort_if((string) ($service->tenant_id ?? '') !== $tenantId, 403);

        $service->forceFill([
            'archived_at' => null,
            'is_active' => true,
        ])->save();

        return redirect()->route('manager.services.index')->with('billing_status', 'Service restored successfully.');
    }
}
