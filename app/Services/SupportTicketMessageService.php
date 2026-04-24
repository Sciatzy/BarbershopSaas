<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SupportTicketMessageService
{
    public function createMessage(
        SupportTicket $ticket,
        Request $request,
        ?User $sender,
        string $senderRole,
        string $message,
        bool $isInternal = false,
    ): SupportTicketMessage {
        $payload = [
            'support_ticket_id' => $ticket->id,
            'sender_user_id' => $sender?->id,
            'sender_role' => $senderRole,
            'message' => trim($message),
            'is_internal' => $isInternal,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            if ($file !== null) {
                $payload['attachment_path'] = $file->store('support-ticket-attachments', 'public');
                $payload['attachment_original_name'] = $file->getClientOriginalName();
                $payload['attachment_mime'] = $file->getClientMimeType();
                $payload['attachment_size'] = $file->getSize();
            }
        }

        return SupportTicketMessage::query()->create($payload);
    }
}
