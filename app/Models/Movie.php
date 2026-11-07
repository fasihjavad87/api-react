<?php

namespace App\Models;

use App\Enums\ContentType;
use App\Enums\MovieStatus;
use App\Enums\PlaybackStatus;
use App\Enums\SchedulePattern;
use App\Models\Concerns\HasContentType;
use App\Models\Concerns\HasPublishSchedule;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Movie extends Model
{
    use HasFactory, HasContentType, HasPublishSchedule, HasMedia;

    protected $fillable = [
        'title',
        'e_name',
        'slug',
        'description',
        'content_type',
        'status',
        'playback_status',
        'runtime',
        'is_free',
        'total_episodes',
        'schedule_pattern',
        'air_days',
        'air_time',
        'first_air_date',
    ];

    protected $casts = [
        'content_type' => ContentType::class,
        'status' => MovieStatus::class,
        'playback_status' => PlaybackStatus::class,
        'schedule_pattern' => SchedulePattern::class,
        'is_free' => 'boolean',
        'first_air_date' => 'date',
        'runtime' => 'integer',
        'total_episodes' => 'integer',
        'air_days' => 'array',
    ];

    protected $with = ['media'];
    protected $appends = ['primary_media'];

    // ==================== Relations ====================

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }

    public function details(): HasOne
    {
        return $this->hasOne(MovieDetail::class);
    }

    // ==================== Scopes ====================

    public function scopePublished($query)
    {
        return $query->where('status', MovieStatus::PUBLISH);
    }

    public function scopeSeries($query)
    {
        return $query->where('content_type', ContentType::SERIES);
    }

    public function scopeMovies($query)
    {
        return $query->where('content_type', ContentType::MOVIE);
    }
}
