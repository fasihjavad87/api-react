<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'release_year',
        'age_rating',
        'rating',
        'seasons',
        'air_schedule_display',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'release_year' => 'integer',
        'seasons' => 'array',
        'air_schedule_display' => 'array'
    ];

    // ==================== Relations ====================

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
