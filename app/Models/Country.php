<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'e_name',
        'code'
    ];



    // public function movies()
    // {
    //     return $this->morphedByMany(Movie::class, 'movieable');
    // }

}
