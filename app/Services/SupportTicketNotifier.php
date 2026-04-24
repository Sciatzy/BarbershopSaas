<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketNotifier
{
    public function __construct(private TenantLifecycleNotifier $tenantLifecycleNotifier) {}

    public function notifyPlatformAdminsAboutNewTicket(SupportTicket $ticket): void
    {
        $ticket->loadMissing(['tenant', 'owner']);

        foreach ($this->platformAdminRecipients() as $admin) {
            $this->tenantLifecycleNotifier->notifyUserWithDetails(
                $admin,
                'New Support Ticket Submitted',
                'A tenant owner submitted a new support ticket that needs platform review.',
                [
                    'Ticket' => (string) $ticket->ticket_number,
                    'Tenant' => (string) ($ticket->tenant?->name ?? $ticket->tenant_id),
                    'Owner' => (string) ($ticket->owner?->name ?? 'Unknown'),
                    'Category' => ucfirst((string) $ticket->category),
                    'Priority' => ucfirst((string) $ticket->priority),
                    'Subject' => (string) $ticket->subject,
                    'Status' => ucfirst((string) $ticket->status),
                ],
                'Review and respond from the Platform Admin dashboard.',
            );
        }
    }

    public function notifyPlatformAdminsAboutReply(SupportTicket $ticket, User $sender): void
    {
        $ticket->loadMissing(['tenant', 'owner']);

        foreach ($this->platformAdminRecipients() as $admin) {
            if ((int) $admin->id === (int) $sender->id) {
                continue;
            }

            $this->tenantLifecycleNotifier->notifyUserWithDetails(
                $admin,
                'Support Ticket Reply Received',
                'A tenant owner replied to an existing support ticket.',
                [
                    'Ticket' => (string) $ticket->ticket_number,
                    'Tenant' => (string) ($ticket->tenant?->name ?? $ticket->tenant_id),
                    'Owner Reply By' => (string) $sender->name,
                    'Current Status' => ucfirst((string) $ticket->status),
                ],
                'Open the ticket from the Platform Admin dashboard to continue the thread.',
            );
        }
    }

    public function notifyTenantOwnerAboutAdminReply(SupportTicket $ticket): void
    {
        $ticket->loadMissing(['tenant', 'owner']);

        if (! $ticket->tenant) {
            return;
        }

        $this->tenantLifecycleNotifier->notifyOwnerWithDetails(
            $ticket->tenant,
            'Support Ticket Updated by Platform Admin',
            'Your support ticket has a new response from the platform team.',
            [
                'Ticket' => (string) $ticket->ticket_number,
                'Subject' => (string) $ticket->subject,
                'Status' => ucfirst((string) $ticket->status),
                'Priority' => ucfirst((string) $ticket->priority),
            ],
            'Open your manager dashboard to review and continue the conversation.',
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function platformAdminRecipients()
    {
        return User::query()
            ->withoutGlobalScopes()
            ->role('Platform Admin')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get();
    }
}
