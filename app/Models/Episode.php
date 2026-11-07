<?php

namespace App\Models;

use App\Enums\EpisodeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'title',
        'episode_number',
        'status',
        'publish_at',
        'runtime',
        'is_free',
    ];

    protected $casts = [
        'status' => EpisodeStatus::class,
        'is_free' => 'boolean',
        'publish_at' => 'datetime',
        'runtime' => 'integer',
        'episode_number' => 'integer',
    ];

    // ==================== Relations ====================

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    // ==================== Scopes ====================

    public function scopePublished($query)
    {
        return $query->where('status', EpisodeStatus::PUBLISHED)
            ->where('publish_at', '<=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', EpisodeStatus::SCHEDULED)
            ->where('publish_at', '>', now());
    }

    // ==================== Helpers ====================

    public function isPublished(): bool
    {
        return $this->status === EpisodeStatus::PUBLISHED
            && $this->publish_at
            && $this->publish_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->status === EpisodeStatus::SCHEDULED
            && $this->publish_at
            && $this->publish_at->isFuture();
    }

    public function secondsTillPublish(): ?int
    {
        if (!$this->publish_at || $this->publish_at->isPast()) {
            return null;
        }

        return (int) now()->diffInSeconds($this->publish_at, false);
    }
}