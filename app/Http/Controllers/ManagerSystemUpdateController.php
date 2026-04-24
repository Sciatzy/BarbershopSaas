<?php

namespace App\Http\Controllers;

use App\Models\TenantSystemRelease;
use App\Services\SystemReleaseSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManagerSystemUpdateController extends Controller
{
    public function __construct(private SystemReleaseSyncService $syncService) {}

    public function apply(Request $request, TenantSystemRelease $tenantRelease): RedirectResponse
    {
        $actor = $request->user();
        $tenantRelease->loadMissing(['tenant', 'systemRelease']);

        if ((string) ($actor?->tenant_id ?? '') === '' || (string) $tenantRelease->tenant_id !== (string) $actor?->tenant_id) {
            abort(403);
        }

        if ((string) $tenantRelease->state === 'applied') {
            return redirect()
                ->route('manager.dashboard')
                ->with('billing_status', 'This update is already applied for your tenant.');
        }

        $this->syncService->applyTenantRelease($tenantRelease, $actor);

        $displayVersion = (string) ($tenantRelease->systemRelease?->display_version ?: $tenantRelease->systemRelease?->version ?: 'latest release');

        return redirect()
            ->route('manager.dashboard')
            ->with('billing_status', 'Update '.$displayVersion.' applied successfully.');
    }

    public function hold(Request $request, TenantSystemRelease $tenantRelease): RedirectResponse
    {
        $actor = $request->user();
        $tenantRelease->loadMissing(['tenant', 'systemRelease']);

        if ((string) ($actor?->tenant_id ?? '') === '' || (string) $tenantRelease->tenant_id !== (string) $actor?->tenant_id) {
            abort(403);
        }

        if ((string) $tenantRelease->state === 'applied') {
            return redirect()
                ->route('manager.dashboard')
                ->with('billing_error', 'Applied updates cannot be placed on hold.');
        }

        $validated = $request->validate([
            'hold_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->syncService->holdTenantRelease($tenantRelease, $validated['hold_note'] ?? null);

        $displayVersion = (string) ($tenantRelease->systemRelease?->display_version ?: $tenantRelease->systemRelease?->version ?: 'latest release');

        return redirect()
            ->route('manager.dashboard')
            ->with('billing_status', 'Update '.$displayVersion.' is now on hold.');
    }
}
