<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * تبدیل تاریخ میلادی به شمسی + نمایش به وقت تهران — بخش ۲۲.
 * به‌جای افزودن یک پکیج Composer جدید فقط برای این یک کار، از یک الگوریتم
 * استاندارد و شناخته‌شده تبدیل میلادی→شمسی استفاده شده (بدون وابستگی
 * بیرونی جدید).
 */
class JalaliDate
{
    private const MONTHS = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    /** مثال خروجی پیش‌فرض: ۱۴۰۵/۰۶/۳۱ - ۱۳:۱۹ */
    public static function format(\DateTimeInterface|string $date, string $format = 'Y/m/d - H:i'): string
    {
        $carbon = ($date instanceof \DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date))
            ->setTimezone('Asia/Tehran');

        [$jy, $jm, $jd] = self::toJalali((int) $carbon->format('Y'), (int) $carbon->format('n'), (int) $carbon->format('j'));

        $replacements = [
            'Y' => self::toPersianDigits((string) $jy),
            'm' => self::toPersianDigits(str_pad((string) $jm, 2, '0', STR_PAD_LEFT)),
            'n' => self::toPersianDigits((string) $jm),
            'd' => self::toPersianDigits(str_pad((string) $jd, 2, '0', STR_PAD_LEFT)),
            'j' => self::toPersianDigits((string) $jd),
            'M' => self::MONTHS[$jm - 1],
            'H' => self::toPersianDigits($carbon->format('H')),
            'i' => self::toPersianDigits($carbon->format('i')),
        ];

        return strtr($format, $replacements);
    }

    /** @return array{0:int,1:int,2:int} [سال، ماه، روز] شمسی */
    private static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666
            + (365 * $gy)
            + (int) (($gy2 + 3) / 4)
            - (int) (($gy2 + 99) / 100)
            + (int) (($gy2 + 399) / 400)
            + $gd
            + $gDaysInMonth[$gm - 1];

        $jy = -1595 + (33 * (int) ($days / 12053));
        $days %= 12053;
        $jy += 4 * (int) ($days / 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + (int) ($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int) (($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    private static function toPersianDigits(string $input): string
    {
        return strtr($input, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
