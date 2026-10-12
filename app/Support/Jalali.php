<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * تبدیل و قالب‌بندی تاریخ میلادی به شمسی (بدون وابستگی به بسته‌های بیرونی).
 */
class Jalali
{
    public const MONTHS = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];

    public const WEEKDAYS = [
        'شنبه',
        'یک‌شنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنج‌شنبه',
        'جمعه',
    ];

    /**
     * تبدیل ارقام لاتین به ارقام فارسی (۰ تا ۹).
     */
    public static function toPersianDigits(int|float|string|null $value, bool $trimDecimals = true): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value)) {
            $value = $trimDecimals && floor($value) === $value
                ? (string) (int) $value
                : (string) $value;
        }

        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            (string) $value
        );
    }

    /**
     * تبدیل ارقام فارسی/عربی به ارقام لاتین (برای تجزیه ورودی فرم‌ها).
     */
    public static function toLatinDigits(?string $value): string
    {
        return str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '،'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ','],
            (string) $value
        );
    }

    /**
     * Convert a Gregorian date to Jalali.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function fromGregorian(int $year, int $month, int $day): array
    {
        $daysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy = $month > 2 ? $year + 1 : $year;

        $days = 355666
            + (365 * $year)
            + intdiv($gy + 3, 4)
            - intdiv($gy + 99, 100)
            + intdiv($gy + 399, 400)
            + $day
            + $daysInMonth[$month - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [(int) $jy, (int) $jm, (int) $jd];
    }

    /**
     * Convert a Jalali date to Gregorian.
     *
     * @return array{0: int, 1: int, 2: int} [year, month, day]
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;

        $days = -355668
            + (365 * $jy)
            + (intdiv($jy, 33) * 8)
            + intdiv(($jy % 33) + 3, 4)
            + $jd
            + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;

            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $isLeap = ($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0;
        $months = [0, 31, $isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        $gm = 0;

        while ($gm < 13 && $gd > $months[$gm]) {
            $gd -= $months[$gm];
            $gm++;
        }

        return [(int) $gy, (int) $gm, (int) $gd];
    }

    /**
     * تجزیه ورودی تاریخ فرم‌ها: «۱۴۰۵/۰۷/۲۰» (شمسی) یا «2026-10-12» (میلادی).
     */
    public static function parseInput(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim(self::toLatinDigits($value));

        if ($value === '') {
            return null;
        }

        $value = str_replace(['-', '.'], '/', $value);

        if (! preg_match('#^(\d{3,4})/(\d{1,2})/(\d{1,2})$#', $value, $matches)) {
            return null;
        }

        [, $year, $month, $day] = array_map('intval', $matches);

        if ($year < 1700) {
            [$year, $month, $day] = static::toGregorian($year, $month, $day);
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::createFromDate($year, $month, $day)->startOfDay();
    }

    /**
     * قالب ورودی فرم‌ها برای نمایش تاریخ شمسی، مثل «۱۴۰۵/۰۷/۲۰».
     */
    public static function inputValue(Carbon|\DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        [$year, $month, $day] = static::fromGregorian((int) $carbon->year, (int) $carbon->month, (int) $carbon->day);

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    /**
     * Format a date as e.g. «۲۰ مهر ۱۴۰۵».
     */
    public static function format(Carbon|\DateTimeInterface|string|null $date, bool $withWeekday = false): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $carbon = $date instanceof Carbon
            ? $date->copy()
            : Carbon::parse($date);

        [$year, $month, $day] = static::fromGregorian((int) $carbon->year, (int) $carbon->month, (int) $carbon->day);

        $label = self::toPersianDigits($day).' '.self::MONTHS[$month].' '.self::toPersianDigits($year);

        if ($withWeekday) {
            $label = self::weekday($carbon).'، '.$label;
        }

        return $label;
    }

    /**
     * Format a date and time as e.g. «۲۰ مهر ۱۴۰۵ — ۱۴:۳۰».
     */
    public static function formatDateTime(Carbon|\DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return static::format($carbon).' — '.self::toPersianDigits($carbon->format('H:i'));
    }

    public static function weekday(Carbon|\DateTimeInterface $date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Carbon: 0 = Sunday ... 6 = Saturday  →  Jalali week starts on Saturday
        return self::WEEKDAYS[($carbon->dayOfWeek + 1) % 7];
    }
}
