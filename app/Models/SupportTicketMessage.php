<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketMessage extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'sender_user_id',
        'sender_role',
        'is_internal',
        'message',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'attachment_size',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'attachment_size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function hasAttachment(): bool
    {
        return trim((string) $this->attachment_path) !== '';
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->hasAttachment()) {
            return null;
        }

        return asset('storage/'.ltrim((string) $this->attachment_path, '/'));
    }
}
