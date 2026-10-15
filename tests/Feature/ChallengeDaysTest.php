<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * «باکس روزها»: روزهای چالش از بازه تاریخ شروع و پایان به‌طور پیش‌فرض
 * ساخته و فعال می‌شوند و برای هر روز جداگانه عنوان، توضیحات، کار، پرسش و
 * نکته تعریف می‌شود.
 */
class ChallengeDaysTest extends TestCase
{
    use RefreshDatabase;

    /**
     * چالش نمونه با بازه پنج روزه (روز ۱ تا ۵).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش پنج روزه',
            'slug' => 'five-days',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => Carbon::today()->subDays(2),
            'ends_on' => Carbon::today()->addDays(2),
        ], $attributes));
    }

    /**
     * شماره روزهایی که در HTML به شکل باکس روز ساخته شده‌اند.
     *
     * @return list<int>
     */
    private function boxNumbers(string $html): array
    {
        preg_match_all('/id="day-(\d+)"/', $html, $matches);

        return array_map('intval', $matches[1]);
    }

    /**
     * تکه HTML مربوط به باکس یک روز.
     */
    private function dayBox(string $html, int $day, ?string $until = null): string
    {
        // نشانهٔ دقیقِ خودِ باکس روز؛ زیربخش‌های همان روز id مثل day-3-tips دارند
        $start = strpos($html, 'id="day-'.$day.'" class="');

        $this->assertNotFalse($start, 'باکس روز '.$day.' ساخته نشده است.');

        $end = strpos($html, 'id="day-'.($day + 1).'" class="', $start);

        // برای آخرین باکس (یا وقتی بعد از آن بخش دیگری می‌آید) انتهای محدوده را
        // با یک نشانه صریح مشخص می‌کنیم تا متن بخش‌های بعدی داخل باکس نیفتد.
        if ($until !== null) {
            $marker = strpos($html, $until, $start);

            if ($marker !== false && ($end === false || $marker < $end)) {
                $end = $marker;
            }
        }

        return substr($html, $start, $end === false ? null : $end - $start);
    }

    public function test_days_are_active_by_default_from_the_start_and_end_dates(): void
    {
        $challenge = $this->makeChallenge();

        $this->assertSame(5, $challenge->totalDays());
        $this->assertSame([1, 2, 3, 4, 5], $challenge->dayBoxNumbers()->all());
        $this->assertSame(
            0,
            $challenge->days()->count(),
            'روزها بدون ثبت دستی فعال‌اند؛ جدول روزها فقط برای عنوان و توضیحات است.'
        );

        $this->loginAsAdmin();

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertSame([1, 2, 3, 4, 5], $this->boxNumbers($html));
        $this->assertStringContainsString('روزهای چالش', $html);
        $this->assertStringNotContainsString('فعال‌سازی روز', $html);
    }

    public function test_the_day_zero_box_only_appears_when_it_has_content(): void
    {
        $challenge = $this->makeChallenge();
        $this->loginAsAdmin();

        $withoutContent = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
        $this->assertSame([1, 2, 3, 4, 5], $this->boxNumbers($withoutContent));

        $challenge->tasks()->create(['day_number' => null, 'title' => 'کار بدون روز مشخص']);

        $withContent = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
        $this->assertSame([0, 1, 2, 3, 4, 5], $this->boxNumbers($withContent));
    }

    public function test_each_day_has_its_own_title_and_description(): void
    {
        $challenge = $this->makeChallenge();
        $this->loginAsAdmin();

        $this->put(route('panel.days.update', [$challenge, 3]), [
            'title' => 'روز آرامش',
            'description' => 'امروز فقط نفس بکش و آهسته جلو برو.',
        ])->assertRedirect();

        $this->assertDatabaseHas('challenge_days', [
            'challenge_id' => $challenge->id,
            'day_number' => 3,
            'title' => 'روز آرامش',
        ]);

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('روز آرامش', $this->dayBox($html, 3));
        $this->assertStringContainsString('امروز فقط نفس بکش و آهسته جلو برو.', $this->dayBox($html, 3));
        $this->assertStringNotContainsString('روز آرامش', $this->dayBox($html, 2));
    }

    public function test_emptying_the_title_and_description_resets_the_day(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->days()->create(['day_number' => 2, 'title' => 'عنوان قبلی', 'description' => 'توضیح قبلی']);

        $this->loginAsAdmin();

        $this->put(route('panel.days.update', [$challenge, 2]), ['title' => '', 'description' => ''])
            ->assertRedirect();

        $this->assertDatabaseMissing('challenge_days', [
            'challenge_id' => $challenge->id,
            'day_number' => 2,
        ]);
    }

    public function test_day_settings_are_rejected_outside_the_allowed_range(): void
    {
        $challenge = $this->makeChallenge();
        $this->loginAsAdmin();

        $this->put(route('panel.days.update', [$challenge, 0]), ['title' => 'روز صفر'])->assertNotFound();
        $this->put(route('panel.days.update', [$challenge, 366]), ['title' => 'روز ۳۶۶'])->assertNotFound();

        $this->put(route('panel.days.update', [$challenge, 4]), ['title' => str_repeat('ا', 191)])
            ->assertSessionHasErrors('title');
    }

    public function test_tasks_questions_and_tips_are_defined_per_day_inside_that_day_box(): void
    {
        $challenge = $this->makeChallenge();
        $this->loginAsAdmin();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'task',
            'date' => Jalali::inputValue($challenge->dateForDay(4)),
            'title' => 'کار روز چهارم',
        ])->assertRedirect();

        $this->post(route('panel.questions.store', $challenge), [
            'day_number' => 4,
            'question' => 'پرسش روز چهارم؟',
            'is_published' => 1,
        ])->assertRedirect();

        $this->post(route('panel.tips.store', $challenge), [
            'day_number' => 4,
            'emoji' => '💡',
            'title' => 'نکته روز چهارم',
            'body' => 'متن نکته روز چهارم',
        ])->assertRedirect();

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
        $dayFour = $this->dayBox($html, 4);

        $this->assertStringContainsString('کار روز چهارم', $dayFour);
        $this->assertStringContainsString('پرسش روز چهارم؟', $dayFour);
        $this->assertStringContainsString('نکته روز چهارم', $dayFour);

        $this->assertStringNotContainsString('کار روز چهارم', $this->dayBox($html, 3));
        $this->assertStringNotContainsString('نکته روز چهارم', $this->dayBox($html, 5));
    }

    public function test_general_tips_stay_out_of_the_day_boxes(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);
        $challenge->tips()->create(['title' => 'نکته کلی چالش', 'emoji' => '📌']);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('نکته کلی چالش', $html);

        // نکتهٔ کلی فقط در بخش «نکته‌های کلی» (id="tips") می‌آید، نه داخل باکس روزها
        // نکتهٔ کلی در باکس «بدون روز مشخص» (★) و در بخش نکته‌های کلی هست،
        // اما داخل باکس روزهای شماره‌دار (۱ تا ۵) نمی‌افتد. مودال‌های افزودن
        // بعد از آخرین باکس رندر می‌شوند، پس انتهای باکس آخر را نشانهٔ مودال می‌بریم.
        foreach ([1, 2, 3, 4, 5] as $day) {
            $until = $day === 5 ? '<dialog id="modal-content"' : 'id="tips"';

            $this->assertStringNotContainsString('نکته کلی چالش', $this->dayBox($html, $day, $until));
        }

        // و داخل خود بخش نکته‌های کلی نشان داده می‌شود
        $tipsStart = strpos($html, 'id="tips"');
        $modalStart = strpos($html, '<dialog id="modal-content"');

        $this->assertNotFalse($modalStart, 'مودال افزودن باید بعد از بخش‌های صفحه باشد.');

        $tipsSection = substr($html, (int) $tipsStart, $modalStart - (int) $tipsStart);
        $this->assertStringContainsString('نکته کلی چالش', $tipsSection);
    }

    public function test_a_participant_sees_every_day_of_the_challenge_including_empty_days(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'زهرا']);

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();

        // روزهای باز کارت دارند و روزهای قفل در یک نوار با تاریخ‌شان فهرست می‌شوند
        $openDays = [];
        $lockedDays = [];

        foreach (range(1, $challenge->totalDays()) as $day) {
            $challenge->isDayUnlocked($day) ? $openDays[] = $day : $lockedDays[] = $day;
        }

        $this->assertSame($openDays, $this->boxNumbers($html), 'روزهای باز باید کارت داشته باشند.');
        $this->assertStringContainsString('کار روز اول', $html);

        if ($lockedDays !== []) {
            $this->assertStringContainsString('id="locked-days"', $html);
        }

        foreach ($lockedDays as $day) {
            $this->assertStringContainsString(
                fa_date($challenge->dateForDay($day)),
                $html,
                'تاریخ روز قفل '.$day.' روی صفحه نیست.'
            );
        }
    }

    public function test_a_participant_sees_the_title_and_description_of_open_days(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->days()->create(['day_number' => 1, 'title' => 'شروع آرام', 'description' => 'توضیح روز اول']);
        $challenge->days()->create(['day_number' => 5, 'title' => 'روز پایانی', 'description' => 'توضیح روز آخر']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'امیر']);

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();

        $this->assertStringContainsString('شروع آرام', $html);
        $this->assertStringContainsString('توضیح روز اول', $html);
    }

    public function test_the_public_page_hides_the_title_and_description_of_locked_days(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->days()->create(['day_number' => 1, 'title' => 'روز باز چالش']);
        $challenge->days()->create(['day_number' => 5, 'title' => 'روز قفل چالش']);

        $html = $this->get($challenge->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('روز باز چالش', $html);
        $this->assertStringNotContainsString('روز قفل چالش', $html);
    }

    public function test_the_participant_dashboard_of_the_admin_shows_the_date_and_title_of_each_day(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->days()->create(['day_number' => 2, 'title' => 'حرکت و آب']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز دوم']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'نگار']);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.participants.show', $participant))->assertOk()->getContent();

        $this->assertStringContainsString('حرکت و آب', $html);
        $this->assertStringContainsString(fa_date($challenge->dateForDay(2)), $html);
        $this->assertStringContainsString('روز '.fa_num(2), $html);
    }

    public function test_tips_of_a_locked_day_are_not_shown_to_the_participant(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tips()->create(['day_number' => 5, 'title' => 'نکته روز قفل', 'emoji' => '🔒']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertDontSee('نکته روز قفل');
    }
}
