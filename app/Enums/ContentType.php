<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ContentType:string
{
    use HasValues;

    case MOVIE= 'movie';
    case SERIES= 'series';

    public function label():string
    {
        return match($this){
            self::MOVIE=>'فیلم',
            self::SERIES=>'سریال',
        };
    }

}
