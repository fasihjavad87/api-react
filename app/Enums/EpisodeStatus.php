<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum EpisodeStatus:string
{
    use HasValues;

    case DRAFT= 'draft';
    case SCHEDULED= 'scheduled';
    case PUBLISHED= 'published';


    public function label():string
    {
        return match($this){
            self::DRAFT=>'پیش‌نویس',
            self::SCHEDULED=>'زمان‌بندی شده',
            self::PUBLISHED=>'منتشر شده',
        };
    }
}
