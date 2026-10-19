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
     * قاعده‌های اعتبارسنجی فیلد تاریخ روز.
     *
     * @return array<int, mixed>
     */
    protected function dateRules(Challenge $challenge): array
    {
        return ['nullable', 'string', 'max:16', $this->jalaliDateRule(), $this->onOrAfterStartRule($challenge)];
    }

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
     * روزهای چالش از تاریخ شروع شمرده می‌شوند؛ پس تاریخ قبل از شروع معنا ندارد.
     */
    protected function onOrAfterStartRule(Challenge $challenge): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($challenge): void {
            $date = Jalali::parseInput(is_string($value) ? $value : null);

            if (! $date || $date->greaterThanOrEqualTo($challenge->dayOneDate())) {
                return;
            }

            $start = $challenge->dayOneDate();

            $fail('این تاریخ قبل از شروع چالش است؛ روز ۱ این چالش '.fa_date($start, true).' است. '
                .'برای اضافه‌کردن کار یا پرسش به روزهای گذشته، یکی از روزهای فهرست «روزهای چالش» را انتخاب کن '
                .'یا اگر می‌خواهی چالش از روزهای قبل شروع شود، تاریخ شروع را در «ویرایش مشخصات» عقب‌تر ببر.');
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
