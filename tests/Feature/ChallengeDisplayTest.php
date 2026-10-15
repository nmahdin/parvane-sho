<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * نمایش درست تعداد کل روزها، متن باکس قفل، پرسش‌های بدون پاسخ و آیکون سایت.
 */
class ChallengeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * چالشی ۷ روزه که امروز روز ۵ آن است ولی فقط روزهای ۱ و ۲ محتوا دارند.
     */
    private function challengeWithPartialContent(): Challenge
    {
        $challenge = Challenge::create([
            'title' => 'چالش هفت روزه',
            'slug' => 'seven-days',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today()->subDays(4),
            'ends_on' => today()->addDays(2),
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز ۱']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز ۲']);

        return $challenge->fresh();
    }

    public function test_total_days_does_not_depend_on_the_days_that_have_content(): void
    {
        $challenge = $this->challengeWithPartialContent();

        // تعداد کل روزها از تاریخ شروع و پایان می‌آید، نه از روزهای محتوا
        $this->assertSame(2, $challenge->contentDaysCount());
        $this->assertSame(7, $challenge->totalDays());

        // امروز روز ۵ چالش است؛ روز جاری هم به محتوا وابسته نیست
        $this->assertSame(5, $challenge->todayDayNumber());
        $this->assertSame(5, $challenge->currentDayNumber());
    }

    public function test_pages_show_the_full_number_of_days(): void
    {
        $challenge = $this->challengeWithPartialContent();
        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        // صفحه چالش: «۷ روز» (نه ۲ روزی که محتوا دارند)
        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('تعداد روزها')
            ->assertSee('۷ روز')
            ->assertDontSee('۲ روز');

        // داشبورد شرکت‌کننده: «روز ۵ از ۷»
        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('روز ۵ از ۷');

        // فهرست چالش‌ها و صفحه اصلی هم همان ۷ روز را نشان می‌دهند
        $this->get(route('challenges.index'))->assertOk()->assertSee('۷ روز');

        $this->get(route('home'))->assertOk()->assertSee('۷ روز');

        // پنل: فهرست چالش‌ها و صفحه مدیریت چالش
        $this->loginAsAdmin();

        $this->get(route('panel.challenges.index'))->assertOk()->assertSee('۷ روز');

        // پنل: فهرست «روزهای چالش» تا روز ۷ (بر اساس تاریخ پایان) باز می‌شود
        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('روزها')
            ->assertSee('روز ۷')
            ->assertSee('روز ۵');
    }

    public function test_a_challenge_without_end_date_counts_until_today(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش بی‌پایان',
            'slug' => 'open-ended',
            'is_published' => true,
            'starts_on' => today()->subDays(2),
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز ۱']);

        // بدون تاریخ پایان: تا بزرگ‌ترین روزی که محتوا دارد یا امروز
        $this->assertSame(3, $challenge->fresh()->totalDays());
        $this->assertSame(3, $challenge->fresh()->currentDayNumber());
    }

    public function test_locked_day_shows_the_lock_word_only_once(): void
    {
        $challenge = $this->challengeWithPartialContent();
        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        // روز ۶ (فردا) هنوز قفل است
        $challenge->tasks()->create(['day_number' => 6, 'title' => 'کار فردا']);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('قفل')
            ->assertDontSee('(قفل)')
            ->assertDontSee('🔒</p>');

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertDontSee('(قفل)')
            ->assertDontSee('🔒</p>')
            ->assertDontSee('روز قفل است');
    }

    public function test_questions_have_no_answer_by_default(): void
    {
        $challenge = $this->challengeWithPartialContent();
        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $this->loginAsAdmin();

        // فرم پنل فقط فیلد متن پرسش را دارد
        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('متن پرسش')
            ->assertDontSee('name="answer"');

        // ثبت پرسش بدون پاسخ
        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'date' => Jalali::inputValue($challenge->dateForDay(1)),
            'question' => 'امروز بابت چه چیزی شکرگزار بودی؟',
        ])->assertRedirect();

        $question = $challenge->questions()->firstOrFail();
        $this->assertNull($question->answer);

        $this->post(route('panel.logout'));

        // صفحه چالش: پرسش دیده می‌شود، ولی هیچ متن پاسخ پیش‌فرضی نیست
        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('امروز بابت چه چیزی شکرگزار بودی؟')
            ->assertDontSee('پاسخ این پرسش هنوز ثبت نشده است.')
            ->assertDontSee('بدون پاسخ');

        // و داشبورد شرکت‌کننده هم جای پاسخ دادن دارد
        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('پاسخ تو به این پرسش');
    }

    public function test_homepage_has_no_written_code(): void
    {
        $challenge = $this->challengeWithPartialContent();
        $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('k7m2p9qz')
            ->assertDontSee('کد اختصاصی:');
    }

    public function test_the_site_ships_the_jade_butterfly_icon(): void
    {
        $svg = public_path('favicon.svg');
        $this->assertFileExists($svg);
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));

        $this->assertStringContainsString('#11735b', file_get_contents($svg));

        // همه صفحات (سایت و پنل) آیکون را معرفی می‌کنند و لوگو با تصویر پروانه است
        $this->get(route('home'))->assertOk()->assertSee('favicon.svg', false);
        $this->get(route('panel.login'))->assertOk()->assertSee('favicon.svg', false);
    }
}
