<?php

namespace Tests\Unit;

use App\Support\Jalali;
use App\Support\TextList;
use PHPUnit\Framework\TestCase;

class JalaliTest extends TestCase
{
    public function test_it_converts_gregorian_dates_to_jalali(): void
    {
        $this->assertSame([1405, 7, 20], Jalali::fromGregorian(2026, 10, 12));
        $this->assertSame([1404, 1, 1], Jalali::fromGregorian(2025, 3, 21));
        $this->assertSame([1399, 12, 30], Jalali::fromGregorian(2021, 3, 20));
    }

    public function test_it_converts_jalali_dates_back_to_gregorian(): void
    {
        $this->assertSame([2026, 10, 12], Jalali::toGregorian(1405, 7, 20));
        $this->assertSame([2025, 3, 21], Jalali::toGregorian(1404, 1, 1));
    }

    public function test_it_parses_form_input_in_both_calendars(): void
    {
        $jalali = Jalali::parseInput('۱۴۰۵/۰۷/۲۰');
        $this->assertNotNull($jalali);
        $this->assertSame('2026-10-12', $jalali->toDateString());

        $gregorian = Jalali::parseInput('2026-10-12');
        $this->assertNotNull($gregorian);
        $this->assertSame('2026-10-12', $gregorian->toDateString());

        $this->assertNull(Jalali::parseInput('چهل و دو'));
        $this->assertNull(Jalali::parseInput('1405/13/40'));
    }

    public function test_it_formats_dates_for_display_and_inputs(): void
    {
        $date = Jalali::parseInput('1405/07/20');

        $this->assertSame('۲۰ مهر ۱۴۰۵', Jalali::format($date));
        $this->assertSame('1405/07/20', Jalali::inputValue($date));
    }

    public function test_text_list_cleans_bullets_and_numbers(): void
    {
        $items = TextList::lines("زهرا محمدی\n- امیر رضایی\n۲) نگار کریمی\n\n1. سارا احمدی\n  ");

        $this->assertSame(['زهرا محمدی', 'امیر رضایی', 'نگار کریمی', 'سارا احمدی'], $items);
    }

    public function test_it_converts_digits_in_both_directions(): void
    {
        $this->assertSame('۱۴۰۵', Jalali::toPersianDigits(1405));
        $this->assertSame('۱۲', Jalali::toPersianDigits(12.0));
        $this->assertSame('1405', Jalali::toLatinDigits('۱۴۰۵'));
        $this->assertSame('1405', Jalali::toLatinDigits('١٤٠٥'));
    }
}
