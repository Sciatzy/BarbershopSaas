<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\SupportTicketMessageService;
use App\Services\SupportTicketNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSupportTicketController extends Controller
{
    public function __construct(
        private SupportTicketNotifier $supportTicketNotifier,
        private SupportTicketMessageService $messageService,
    ) {}

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $status = (string) $validated['status'];

        $ticket->forceFill([
            'status' => $status,
            'resolved_at' => $status === 'resolved' ? now() : null,
            'closed_at' => $status === 'closed' ? now() : null,
            'latest_reply_at' => $ticket->latest_reply_at ?? now(),
        ])->save();

        return redirect()
            ->route('admin.dashboard')
            ->with('billing_status', 'Ticket '.$ticket->ticket_number.' status updated to '.str_replace('_', ' ', $status).'.');
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'status' => ['nullable', 'in:open,in_progress,resolved,closed'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,txt,csv,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $actor = $request->user();

        $this->messageService->createMessage(
            ticket: $ticket,
            request: $request,
            sender: $actor,
            senderRole: 'platform_admin',
            message: (string) $validated['message'],
        );

        $nextStatus = (string) ($validated['status'] ?? ($ticket->status === 'open' ? 'in_progress' : $ticket->status));

        $ticket->forceFill([
            'status' => $nextStatus,
            'latest_reply_at' => now(),
            'resolved_at' => $nextStatus === 'resolved' ? now() : null,
            'closed_at' => $nextStatus === 'closed' ? now() : null,
        ])->save();

        $this->supportTicketNotifier->notifyTenantOwnerAboutAdminReply($ticket);

        return redirect()
            ->route('admin.dashboard')
            ->with('billing_status', 'Reply posted to ticket '.$ticket->ticket_number.'.');
    }
}
