<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SchedulePattern: string
{
    use HasValues;

    case WEEKLY = 'weekly';
    case MULTI_WEEKLY = 'multi_weekly';
    case DAILY = 'daily';
    case BINGE = 'binge';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'هفتگی (تک‌روزه)',
            self::MULTI_WEEKLY => 'هفتگی (چندروزه)',
            self::DAILY => 'روزانه',
            self::BINGE => 'یک‌جا (Binge)',
            self::CUSTOM => 'دستی',
        };
    }
}
