<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MovieStatus:string
{
    use HasValues;

    case DRAFT = 'draft';
    case PUBLISH = 'publish';


    public function label():string
    {
        return match($this){
            self::DRAFT=>'پیش‌نویس',
            self::PUBLISH=>'منتشر شده',
        };
    }
}
