<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PlaybackStatus:string
{
    use HasValues;

    case UPCOMING = 'upcoming';
    case AIRING = 'airing';
    case ENDED = 'ended';

    public function label():string
    {
        return match($this){
            self::UPCOMING=>'به زودی',
            self::AIRING=>'در حال پخش',
            self::ENDED=>'پایان یافته',
        };
    }
}
