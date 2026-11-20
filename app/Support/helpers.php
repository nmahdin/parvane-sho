<?php

use App\Support\Jalali;
use Illuminate\Support\Carbon;

if (! function_exists('fa_num')) {
    /**
     * تبدیل ارقام لاتین یک مقدار به ارقام فارسی.
     */
    function fa_num(int|float|string|null $value, bool $trimDecimals = true): string
    {
        return Jalali::toPersianDigits($value, $trimDecimals);
    }
}

if (! function_exists('fa_digits_to_latin')) {
    /**
     * تبدیل ارقام فارسی/عربی به ارقام لاتین (برای تجزیه ورودی فرم‌ها).
     */
    function fa_digits_to_latin(?string $value): string
    {
        return Jalali::toLatinDigits($value);
    }
}

if (! function_exists('fa_date')) {
    /**
     * نمایش تاریخ به صورت شمسی، مثل «۲۰ مهر ۱۴۰۵».
     */
    function fa_date(Carbon|DateTimeInterface|string|null $date, bool $withWeekday = false): string
    {
        return Jalali::format($date, $withWeekday);
    }
}

if (! function_exists('fa_datetime')) {
    /**
     * نمایش تاریخ و ساعت به صورت شمسی.
     */
    function fa_datetime(Carbon|DateTimeInterface|string|null $date): string
    {
        return Jalali::formatDateTime($date);
    }
}
