<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تاریخ هر روز کنار شماره روز (در داشبورد شرکت‌کننده و در پنل مسئول)
 * و جای باکس فایل‌ها در داشبورد شرکت‌کننده.
 */
class DayDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_participant_dashboard_writes_the_date_next_to_the_day_number(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => today()->subDays(2)]);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز دوم']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $response = $this->get(route('participant.show', $participant))->assertOk();

        $response->assertSee('روز ۱', false);
        $response->assertSee(fa_date($challenge->dateForDay(1)));
        $response->assertSee(fa_date($challenge->dateForDay(2)));
    }

    public function test_the_admin_view_of_a_participant_writes_the_date_next_to_the_day_number(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => today()->subDays(2)]);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز دوم']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->loginAsAdmin();

        $response = $this->get(route('panel.participants.show', $participant))->assertOk();

        $response->assertSee('وضعیت روز به روز');
        $response->assertSee(fa_date($challenge->dateForDay(1)));
        $response->assertSee(fa_date($challenge->dateForDay(2)));
    }

    public function test_the_public_challenge_page_writes_the_day_dates(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => today()->subDays(2)]);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee(fa_date($challenge->dateForDay(1)));
    }

    public function test_the_locked_days_card_is_gone_and_the_files_box_took_its_place(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => true, 'starts_on' => today()]);
        $challenge->tasks()->create(['day_number' => 3, 'title' => 'کار روز سوم']);
        $challenge->attachments()->create([
            'title' => 'راهنمای چالش',
            'path' => 'attachments/guide.pdf',
            'original_name' => 'guide.pdf',
            'size' => 1024,
            'mime' => 'application/pdf',
        ]);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $response = $this->get(route('participant.show', $participant))->assertOk();

        // کارت «روزهای قفل‌شده» حذف شده است…
        $response->assertDontSee('روزهای قفل‌شده');

        // …و باکس فایل‌ها در همان ردیف آمده است
        $response->assertSee('فایل‌های این چالش');
        $response->assertSee('راهنمای چالش');

        // اطلاعات مفید کارت حذف‌شده (روز بعدیِ باز شدن) در کارت روز جاری مانده است
        $response->assertSee('روز هنوز باز نشده', false);
    }

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش تاریخ‌ها',
            'slug' => 'dates',
            'is_published' => true,
            'daily_release' => false,
            'pledge_enabled' => false,
            'starts_on' => today()->subDays(2),
        ], $attributes));
    }
}
