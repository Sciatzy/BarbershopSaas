<?php

namespace App\Models;

use App\Support\Tenancy\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarberCashout extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'tenant_id',
        'barber_id',
        'branch_id',
        'points',
        'amount_php',
        'status',
        'requested_by',
        'approved_by',
        'paid_by',
        'approved_at',
        'paid_at',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'amount_php' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
