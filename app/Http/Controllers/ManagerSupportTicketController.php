<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\SupportTicketMessageService;
use App\Services\SupportTicketNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManagerSupportTicketController extends Controller
{
    public function __construct(
        private SupportTicketNotifier $supportTicketNotifier,
        private SupportTicketMessageService $messageService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $tenantId = (string) ($actor?->tenant_id ?? '');

        if ($tenantId === '') {
            return redirect()->route('manager.dashboard')->with('billing_error', 'No tenant found for this account.');
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:general,bug,billing,performance,security,feature_request,other'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,txt,csv,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $ticket = SupportTicket::query()->create([
            'tenant_id' => $tenantId,
            'owner_user_id' => $actor?->id,
            'subject' => trim((string) $validated['subject']),
            'category' => (string) $validated['category'],
            'priority' => (string) $validated['priority'],
            'status' => 'open',
            'description' => trim((string) $validated['description']),
            'latest_reply_at' => now(),
        ]);

        $this->messageService->createMessage(
            ticket: $ticket,
            request: $request,
            sender: $actor,
            senderRole: 'tenant_owner',
            message: (string) $validated['description'],
        );

        $this->supportTicketNotifier->notifyPlatformAdminsAboutNewTicket($ticket);

        return redirect()
            ->route('manager.dashboard')
            ->with('billing_status', 'Support ticket '.$ticket->ticket_number.' submitted successfully.');
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $actor = $request->user();
        $tenantId = (string) ($actor?->tenant_id ?? '');

        if ($tenantId === '' || (string) $ticket->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,txt,csv,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $this->messageService->createMessage(
            ticket: $ticket,
            request: $request,
            sender: $actor,
            senderRole: 'tenant_owner',
            message: (string) $validated['message'],
        );

        $reopen = in_array((string) $ticket->status, ['resolved', 'closed'], true);

        $ticket->forceFill([
            'status' => $reopen ? 'open' : $ticket->status,
            'latest_reply_at' => now(),
            'resolved_at' => $reopen ? null : $ticket->resolved_at,
            'closed_at' => $reopen ? null : $ticket->closed_at,
        ])->save();

        $this->supportTicketNotifier->notifyPlatformAdminsAboutReply($ticket, $actor);

        return redirect()
            ->route('manager.dashboard')
            ->with('billing_status', 'Reply sent for ticket '.$ticket->ticket_number.'.');
    }
}
