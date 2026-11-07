<?php

namespace App\Services;

class AirScheduleFormatter
{
    private const DAYS_FA = [
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
        6 => 'شنبه',
    ];

    private const DAYS_EN = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public static function format(array $airDays, ?string $airTime): array
    {
        $daysFa = array_map(fn($d) => self::DAYS_FA[$d] ?? '', $airDays);
        $daysEn = array_map(fn($d) => self::DAYS_EN[$d] ?? '', $airDays);

        $timeFa = $airTime ? self::toPersianTime($airTime) : null;
        $timeEn = $airTime ? substr($airTime, 0, 5) : null;

        $textFa = '';
        if (!empty($daysFa)) {
            $daysStr = self::joinPersian($daysFa);
            $textFa = $airTime
                ? "{$daysStr} ساعت {$timeFa}"
                : $daysStr;
        }

        $textEn = '';
        if (!empty($daysEn)) {
            $daysStr = implode(' & ', $daysEn);
            $textEn = $airTime
                ? "{$daysStr} at {$timeEn}"
                : $daysStr;
        }

        return [
            'days' => $daysFa,
            'days_en' => $daysEn,
            'time' => $timeEn,
            'time_fa' => $timeFa,
            'text_fa' => $textFa,
            'text_en' => $textEn,
        ];
    }

    private static function toPersianTime(string $time): string
    {
        $parts = explode(':', $time);
        $hour = str_pad((string) ((int) $parts[0]), 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) ((int) ($parts[1] ?? 0)), 2, '0', STR_PAD_LEFT);

        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $hour = str_replace(range(0, 9), $persian, $hour);
        $minute = str_replace(range(0, 9), $persian, $minute);

        return "{$hour}:{$minute}";
    }

    private static function joinPersian(array $days): string
    {
        $pluralized = array_map(fn($d) => $d . '‌ها', $days);

        if (count($pluralized) === 1) {
            return $pluralized[0];
        }
        if (count($pluralized) === 2) {
            return $pluralized[0] . ' و ' . $pluralized[1];
        }

        $last = array_pop($pluralized);
        return implode('، ', $pluralized) . ' و ' . $last;
    }
}