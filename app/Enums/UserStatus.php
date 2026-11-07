<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserStatus :string
{
    use HasValues;

    case Active = 'active';
    case Banned = 'banned';
    case Verified = 'verified';
    case Unverified = 'unverified';

    public function label(): string
    {
        return match($this) {
            self::Active => 'فعال',
            self::Banned => 'مسدود شده',
            self::Verified => 'تایید شده',
            self::Unverified => 'تایید نشده',
        };
    }
}