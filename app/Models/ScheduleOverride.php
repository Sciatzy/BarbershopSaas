<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleOverride extends Model
{
    protected $fillable = [
        'tenant_id',
        'barber_id',
        'schedule_date',
        'is_working',
        'start_time',
        'end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
            'is_working' => 'boolean',
        ];
    }
}
