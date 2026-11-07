<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;


    protected $table = 'media';

    protected $fillable = [
        'name',
        'file_name',
        'slug',
        'path',
        'disk',
        'mime_type',
        'extension',
        'title',
        'description',
        'size',
        'user_id'
    ];

    // protected $appends = ['url'];
    protected $appends = ['thumb_url', 'detail_url'];

    public function getUrl(?string $preset = null): string
    {
        if (!$this->path) return '';

        if ($preset) {
            // استفاده از هلپر پکیج برای ساخت URL بهینه
            return imagepreset_url($this->path, $preset);
        }

        return asset('storage/' . $this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->getUrl('thumb');
    }

    public function getDetailUrlAttribute(): string
    {
        return $this->getUrl('detail');
    }

    //     public function getUrlAttribute(): string
    //     {
    //         return $this->getUrl();
    //     }

    //     public function getUrl(): string
    // {
    //     return $this->path
    //         ? asset('storage/' . $this->path)
    //         : '';
    // }


     // ==================== Relations ====================

     public function movies()
     {
        return $this->morphedByMany(Movie::class, 'mediable')
        ->withPivot(['index', 'order'])
        ->withTimestamps();
     }

}
