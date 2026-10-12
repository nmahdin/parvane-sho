<?php

namespace App\Http\Controllers\Panel\Concerns;

use App\Models\Challenge;
use App\Support\Jalali;
use Closure;
use Illuminate\Http\Request;

/**
 * تعیین «روز» محتوای چالش (کار یا پرسش) از تاریخ شمسی انتخاب‌شده در فرم.
 */
trait ResolvesChallengeDay
{
    /**
     * قاعده اعتبارسنجی تاریخ شمسی/میلادی ورودی فرم.
     */
    protected function jalaliDateRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && trim($value) !== '' && Jalali::parseInput($value) === null) {
                $fail('تاریخ را درست وارد کنید؛ مثل ۱۴۰۵/۰۷/۲۰.');
            }
        };
    }

    /**
     * روز محتوا: از تاریخ انتخاب‌شده (روز ۱ = روز اول چالش) و در نبود آن از شماره روز.
     */
    protected function resolveDayNumber(Request $request, Challenge $challenge): ?int
    {
        $date = Jalali::parseInput($request->input('date'));

        if ($date) {
            return $challenge->dayNumberForDate($date);
        }

        $dayNumber = $request->input('day_number');

        return filled($dayNumber) ? max(1, (int) $dayNumber) : null;
    }
}
