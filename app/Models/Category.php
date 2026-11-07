<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Override;

class Category extends Model
{
    protected $fillable = [
        'name',
        'e_name',
        'slug'
    ];


	protected static function booted()
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->e_name);
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('e_name') && empty($category->slug)) {
                $category->slug = Str::slug($category->e_name);
            }
        });
    }

    // public function movies()
    // {
    //     return $this->morphedByMany(Movie::class, 'movieable');
    // }


}
