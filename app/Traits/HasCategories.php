<?php

namespace App\Traits;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCategories
{

    /**
     * @mixin \Illuminate\Database\Eloquent\Model
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'movieable');
    }
}
