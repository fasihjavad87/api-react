<?php

namespace App\Traits;

use App\Models\Country;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCountries
{

    /**
     * @mixin \Illuminate\Database\Eloquent\Model
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Country::class, 'movieable');
    }
}
