<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SystemRelease;
use App\Models\Tenant;
use App\Services\TenantLifecycleNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private TenantLifecycleNotifier $notifier) {}

    private const PLAN_MRR_PHP = [
        'starter' => 499,
        'professional' => 1299,
        'business' => 2499,
        'enterprise' => 4999,
    ];

    public function index(Request $request): View
    {
        $tenants = Tenant::query()
            ->with(['latestCashierSubscription', 'owner:id,email,name'])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'plan_tier',
                'status',
                'primary_domain',
                'database_name',
                'database_provisioned_at',
                'activated_at',
                'deactivated_at',
                'owner_user_id',
                'created_at',
            ]);

        $totalMrr = $tenants->sum(
            fn (Tenant $tenant): int => self::PLAN_MRR_PHP[$tenant->plan_tier] ?? 0
        );

        $releases = SystemRelease::query()
            ->with(['syncedBy:id,name'])
            ->withCount([
                'tenantStates as pending_states_count' => fn ($query) => $query->where('state', 'pending'),
                'tenantStates as held_states_count' => fn ($query) => $query->where('state', 'held'),
                'tenantStates as applied_states_count' => fn ($query) => $query->where('state', 'applied'),
            ])
            ->latest('id')
            ->take(12)
            ->get();

        $ticketStatus = trim((string) $request->query('ticket_status', ''));
        $ticketPriority = trim((string) $request->query('ticket_priority', ''));
        $ticketCategory = trim((string) $request->query('ticket_category', ''));
        $ticketSearch = trim((string) $request->query('ticket_search', ''));

        $supportTicketQuery = SupportTicket::query()
            ->withoutGlobalScopes()
            ->with([
                'tenant:id,name',
                'owner:id,name,email',
                'messages' => fn ($query) => $query->with('sender:id,name')->orderBy('created_at'),
            ]);

        if ($ticketStatus !== '') {
            $supportTicketQuery->where('status', $ticketStatus);
        }

        if ($ticketPriority !== '') {
            $supportTicketQuery->where('priority', $ticketPriority);
        }

        if ($ticketCategory !== '') {
            $supportTicketQuery->where('category', $ticketCategory);
        }

        if ($ticketSearch !== '') {
            $supportTicketQuery->where(function ($query) use ($ticketSearch): void {
                $query
                    ->where('ticket_number', 'like', '%'.$ticketSearch.'%')
                    ->orWhere('subject', 'like', '%'.$ticketSearch.'%')
                    ->orWhere('description', 'like', '%'.$ticketSearch.'%')
                    ->orWhereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('name', 'like', '%'.$ticketSearch.'%'))
                    ->orWhereHas('owner', fn ($ownerQuery) => $ownerQuery->where('name', 'like', '%'.$ticketSearch.'%')->orWhere('email', 'like', '%'.$ticketSearch.'%'));
            });
        }

        $supportTickets = $supportTicketQuery
            ->orderByDesc('latest_reply_at')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'ticket_page')
            ->withQueryString();

        return view('admin.dashboard', [
            'tenants' => $tenants,
            'totalMrr' => $totalMrr,
            'planMrrPhp' => self::PLAN_MRR_PHP,
            'systemReleases' => $releases,
            'supportTickets' => $supportTickets,
            'supportTicketFilters' => [
                'status' => $ticketStatus,
                'priority' => $ticketPriority,
                'category' => $ticketCategory,
                'search' => $ticketSearch,
            ],
        ]);
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill([
            'status' => 'suspended',
            'deactivated_at' => now(),
        ])->save();

        $tenant->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->update([
                'stripe_status' => 'suspended',
                'updated_at' => now(),
            ]);

        $this->notifier->notifyOwner(
            $tenant,
            'Tenant Subscription Suspended',
            "Your tenant {$tenant->name} subscription has been suspended by the platform admin."
        );

        return redirect()
            ->route('admin.dashboard')
            ->with('billing_status', "Tenant {$tenant->name} has been suspended.");
    }
}
