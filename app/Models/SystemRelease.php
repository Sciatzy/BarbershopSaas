<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemRelease extends Model
{
    protected $fillable = [
        'version',
        'display_version',
        'publication_status',
        'source',
        'branch',
        'commit_hash',
        'short_commit',
        'release_notes',
        'synced_by_user_id',
        'synced_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function syncedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'synced_by_user_id');
    }

    public function tenantStates(): HasMany
    {
        return $this->hasMany(TenantSystemRelease::class, 'system_release_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', 'published');
    }

    public function isPublished(): bool
    {
        return (string) $this->publication_status === 'published';
    }
}
