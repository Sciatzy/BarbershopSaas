<?php

namespace App\Models;

use App\Support\Tenancy\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsLedger extends Model
{
    use UsesTenantConnection;

    protected $table = 'points_ledger';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'booking_id',
        'type',
        'points',
        'balance_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
