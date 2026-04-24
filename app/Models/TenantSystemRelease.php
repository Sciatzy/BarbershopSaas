<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSystemRelease extends Model
{
	protected $table = 'tenant_system_release_states';

	protected $fillable = [
		'tenant_id',
		'system_release_id',
		'state',
		'hold_note',
		'available_at',
		'responded_at',
		'held_at',
		'applied_at',
		'applied_by_user_id',
	];

	protected function casts(): array
	{
		return [
			'available_at' => 'datetime',
			'responded_at' => 'datetime',
			'held_at' => 'datetime',
			'applied_at' => 'datetime',
		];
	}

	public function tenant(): BelongsTo
	{
		return $this->belongsTo(Tenant::class);
	}

	public function systemRelease(): BelongsTo
	{
		return $this->belongsTo(SystemRelease::class);
	}

	public function appliedBy(): BelongsTo
	{
		return $this->belongsTo(User::class, 'applied_by_user_id');
	}

	public function scopePending(Builder $query): Builder
	{
		return $query->where('state', 'pending');
	}

	public function scopeHeld(Builder $query): Builder
	{
		return $query->where('state', 'held');
	}
}
