<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    protected $fillable = [
        'tenant_id',
        'owner_user_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'description',
        'latest_reply_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'latest_reply_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('support_ticket_tenant_scope', function (Builder $builder): void {
            if (! app()->bound('tenant')) {
                return;
            }

            $tenant = app('tenant');

            if (! is_object($tenant) || empty($tenant->id)) {
                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenant->id);
        });

        static::creating(function (self $ticket): void {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = $ticket->generateTicketNumber();
            }

            if ($ticket->latest_reply_at === null) {
                $ticket->latest_reply_at = Carbon::now();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportTicketMessage::class)->latestOfMany();
    }

    private function generateTicketNumber(): string
    {
        $attempt = 0;

        do {
            $attempt++;
            $candidate = 'STK-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
            $exists = self::query()->withoutGlobalScopes()->where('ticket_number', $candidate)->exists();
        } while ($exists && $attempt < 5);

        return $candidate;
    }
}
