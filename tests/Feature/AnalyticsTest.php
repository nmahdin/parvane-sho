<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\DayFile;
use App\Models\Participant;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * آمار بازدید سایت:
 * - بازدید صفحه‌های عمومی ثبت می‌شود (خانه، چالش، داشبورد، باز شدن کارت روز)
 * - درخواست‌های پنل شمرده نمی‌شوند
 * - پخش صوت از دانلود جدا شمرده می‌شود و seek دوباره حساب نمی‌شود
 * - صفحه «آمار بازدید» پنل روزانه/ماهانه/هر چالش را نشان می‌دهد
 * - مدیر عادی فقط آمار چالش‌های خودش را می‌بیند
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش آمار',
            'slug' => 'analytics-challenge',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => now()->subDays(2),
            'ends_on' => now()->addDays(5),
        ], $attributes));
    }

    private function makeDayFile(Challenge $challenge, bool $audio = true): DayFile
    {
        $name = $audio ? 'note.wav' : 'plan.txt';

        Storage::disk('local')->put('day-files/'.$name, $audio ? 'RIFF....WAVE' : 'برنامه');

        return $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => $audio ? 'صوت روز اول' : 'جدول تمرین',
            'path' => 'day-files/'.$name,
            'original_name' => $name,
            'size' => 12,
            'mime' => $audio ? 'audio/wav' : 'text/plain',
            'is_audio' => $audio,
        ]);
    }

    public function test_public_pages_record_visits(): void
    {
        $challenge = $this->makeChallenge();

        $this->get(route('home'))->assertOk();
        $this->get(route('challenges.show', $challenge))->assertOk();

        $this->assertSame(1, Visit::where('type', Visit::TYPE_HOME)->count());

        $this->assertDatabaseHas('visits', [
            'challenge_id' => $challenge->id,
            'type' => Visit::TYPE_CHALLENGE,
        ]);
    }

    public function test_panel_requests_do_not_record_visits(): void
    {
        $challenge = $this->makeChallenge();
        $this->loginAsAdmin();

        $this->get(route('panel.challenges.show', $challenge))->assertOk();
        $this->get(route('panel.analytics'))->assertOk();

        $this->assertSame(0, Visit::count());
    }

    public function test_day_view_beacon_records_a_day_visit(): void
    {
        $challenge = $this->makeChallenge();

        $this->post(route('challenge.day-views.store', $challenge), ['day_number' => 1])
            ->assertNoContent();

        $this->assertDatabaseHas('visits', [
            'challenge_id' => $challenge->id,
            'type' => Visit::TYPE_DAY,
            'day_number' => 1,
        ]);

        // روز قفل‌شده بازدید حساب نمی‌شود
        $this->post(route('challenge.day-views.store', $challenge), ['day_number' => 60])
            ->assertNotFound();

        // چالش پنهان هم ۴۰۴ می‌شود
        $hidden = $this->makeChallenge(['title' => 'پنهان', 'slug' => 'hidden-one', 'is_published' => false]);
        $this->post(route('challenge.day-views.store', $hidden), ['day_number' => 1])
            ->assertNotFound();
    }

    public function test_dashboard_visit_is_recorded_with_participant(): void
    {
        $challenge = $this->makeChallenge();

        $participant = Participant::create([
            'challenge_id' => $challenge->id,
            'name' => 'بازدیدکننده',
        ]);

        $this->get(route('participant.show', $participant))->assertOk();

        $this->assertDatabaseHas('visits', [
            'type' => Visit::TYPE_DASHBOARD,
            'challenge_id' => $challenge->id,
            'participant_id' => $participant->id,
        ]);
    }

    public function test_audio_plays_are_counted_only_by_real_play_presses(): void
    {
        $challenge = $this->makeChallenge();
        $file = $this->makeDayFile($challenge, audio: true);

        // باز شدن خود فایل (حتی Range بایت ۰ برای preload مرورگر) پخش حساب نمی‌شود
        $this->get(route('challenge.day-files.show', [$challenge, $file]))->assertOk();
        $this->get(route('challenge.day-files.show', [$challenge, $file]), ['HTTP_RANGE' => 'bytes=0-9'])
            ->assertStatus(206);
        $this->assertSame(0, $file->fresh()->plays);

        // پخش‌کنندهٔ صفحه این آدرس را دارد تا با زدن دکمهٔ پخش beacon بفرستد
        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();
        $this->assertStringContainsString('data-audio-play-url', $html);

        // هر بار فشار دادن دکمهٔ پخش = یک پخش
        $this->post(route('challenge.day-files.plays', [$challenge, $file]))->assertNoContent();
        $this->post(route('challenge.day-files.plays', [$challenge, $file]))->assertNoContent();

        $this->assertSame(2, $file->fresh()->plays);
        $this->assertSame(0, $file->fresh()->downloads);

        // فایل غیرصوتی beacon پخش ندارد
        $plain = $this->makeDayFile($challenge, audio: false);
        $this->post(route('challenge.day-files.plays', [$challenge, $plain]))->assertNotFound();

        // چالش پنهان هم ۴۰۴ می‌شود
        $hidden = $this->makeChallenge(['title' => 'چالش پنهان صوتی', 'slug' => 'hidden-audio', 'is_published' => false]);
        $hiddenFile = $this->makeDayFile($hidden, audio: true);
        $this->post(route('challenge.day-files.plays', [$hidden, $hiddenFile]))->assertNotFound();
    }

    public function test_plain_files_count_downloads_not_plays(): void
    {
        $challenge = $this->makeChallenge();
        $file = $this->makeDayFile($challenge, audio: false);

        $this->get(route('challenge.day-files.show', [$challenge, $file]))->assertOk();

        $this->assertSame(1, $file->fresh()->downloads);
        $this->assertSame(0, $file->fresh()->plays);
    }

    public function test_analytics_page_shows_daily_monthly_and_per_challenge_stats(): void
    {
        $challenge = $this->makeChallenge();
        $this->makeDayFile($challenge, audio: true);

        Visit::create(['challenge_id' => $challenge->id, 'type' => Visit::TYPE_CHALLENGE, 'visited_at' => now()]);
        Visit::create(['challenge_id' => $challenge->id, 'type' => Visit::TYPE_DAY, 'day_number' => 1, 'visited_at' => now()]);
        Visit::create(['type' => Visit::TYPE_HOME, 'visited_at' => now()->subDays(40)]);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.analytics'))->assertOk()->getContent();

        $this->assertStringContainsString('آمار بازدید', $html);
        $this->assertStringContainsString('نمودار بازدید — ۳۰ روز اخیر', $html);
        $this->assertStringContainsString('جمع ماهانه (شمسی)', $html);
        $this->assertStringContainsString('چالش آمار', $html);

        // نمودارها: بوم‌ها + دادهٔ JSON برای chart.js
        $this->assertStringContainsString('id="analytics-data"', $html);
        $this->assertStringContainsString('id="chart-daily"', $html);
        $this->assertStringContainsString('id="chart-monthly"', $html);
        $this->assertStringContainsString('id="chart-types"', $html);

        // دو بازدید امروز (چالش + روز) با رقم فارسی
        $this->assertStringContainsString('بازدید امروز', $html);
        $this->assertStringContainsString('۲', $html);

        // فیلتر یک چالش + تفکیک روزبه‌روز
        $filtered = $this->get(route('panel.analytics', ['challenge' => $challenge->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('روزبه‌روز — چالش آمار', $filtered);
        $this->assertStringContainsString('بازدید کارت روز', $filtered);
    }

    public function test_analytics_is_scoped_to_own_challenges_for_a_challenge_admin(): void
    {
        $admin = $this->makeChallengeAdmin(['username' => 'second', 'name' => 'مدیر دوم']);
        $other = $this->makeChallengeAdmin();

        $mine = $this->makeChallenge(['admin_id' => $admin->id]);
        $others = $this->makeChallenge(['title' => 'چالش دیگری', 'slug' => 'other-challenge', 'admin_id' => $other->id]);

        Visit::create(['challenge_id' => $mine->id, 'type' => Visit::TYPE_CHALLENGE, 'visited_at' => now()]);
        Visit::create(['challenge_id' => $others->id, 'type' => Visit::TYPE_CHALLENGE, 'visited_at' => now()]);

        $this->loginAsAdmin($admin);

        $html = $this->get(route('panel.analytics'))->assertOk()->getContent();

        // چالش خودش را می‌بیند ولی چالش مدیر دیگر را نه
        $this->assertStringContainsString('چالش آمار', $html);
        $this->assertStringNotContainsString('چالش دیگری', $html);

        // فیلتر چالش دیگران هم ۴۰۴/بدون داده نیست — فقط نادیده گرفته می‌شود
        $this->get(route('panel.analytics', ['challenge' => $others->id]))->assertOk();

        // ولی مدیر کل هر دو را می‌بیند
        $this->loginAsAdmin($this->superAdmin());
        $superHtml = $this->get(route('panel.analytics'))->assertOk()->getContent();
        $this->assertStringContainsString('چالش دیگری', $superHtml);
    }

    public function test_analytics_requires_login(): void
    {
        $this->get(route('panel.analytics'))->assertRedirect(route('panel.login'));
    }
}
