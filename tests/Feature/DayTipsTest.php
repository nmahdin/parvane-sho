<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use App\Support\ChallengeExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * نکته‌های هر روز: مدیر می‌تواند برای هر روز نکته جدا بگذارد و شرکت‌کننده
 * نکته‌های همان روز را داخل کارت همان روز می‌بیند.
 */
class DayTipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_panel_can_save_a_tip_for_a_specific_day(): void
    {
        $challenge = $this->makeChallenge();
        $this->loginAsAdmin();

        $this->post(route('panel.tips.store', $challenge), [
            'day_number' => 3,
            'emoji' => '🌬️',
            'title' => 'شمارش نفس‌ها',
            'body' => '۴ ثانیه دم، ۷ ثانیه نگه‌داشتن، ۸ ثانیه بازدم.',
        ])->assertRedirect();

        $tip = $challenge->tips()->firstOrFail();

        $this->assertSame(3, $tip->day_number);
        $this->assertTrue($tip->hasDay());

        // نکته بدون روز یعنی نکته کلی
        $this->post(route('panel.tips.store', $challenge), [
            'title' => 'نکته کلی چالش',
        ])->assertRedirect();

        $this->assertSame(2, $challenge->tips()->count());
        $this->assertSame(1, $challenge->generalTips()->count());
        $this->assertSame(1, $challenge->tipsForDay(3)->count());
        $this->assertSame(0, $challenge->tipsForDay(2)->count());
    }

    public function test_the_participant_sees_the_day_tip_inside_that_day(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته مخصوص روز اول']);
        $challenge->tips()->create(['title' => 'نکته کلی چالش']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $response = $this->get(route('participant.show', $participant))->assertOk();

        $response->assertSee('نکته مخصوص روز اول');
        $response->assertSee('نکته‌های این روز');
        $response->assertSee('نکته‌های کلی چالش');

        // نکتهٔ روز، جای دیگری تکرار نمی‌شود
        $this->assertSame(1, substr_count($response->getContent(), 'نکته مخصوص روز اول'));
    }

    public function test_the_public_challenge_page_shows_day_tips_too(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته روز اول برای همه']);
        $challenge->tips()->create(['title' => 'نکته کلی برای همه']);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('نکته روز اول برای همه')
            ->assertSee('نکته کلی برای همه')
            ->assertSee('نکته‌های این روز');
    }

    public function test_locked_days_do_not_reveal_their_tips(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => true, 'starts_on' => today()]);
        $challenge->tasks()->create(['day_number' => 5, 'title' => 'کار روز پنجم']);
        $challenge->tips()->create(['day_number' => 5, 'title' => 'نکته سری روز پنجم']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertDontSee('نکته سری روز پنجم');
    }

    public function test_a_day_with_only_a_tip_still_gets_its_own_card(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tips()->create(['day_number' => 2, 'title' => 'تنها نکته روز دوم']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('روز ۲', false)
            ->assertSee('تنها نکته روز دوم');
    }

    public function test_a_tip_can_be_moved_to_another_day_or_back_to_general(): void
    {
        $challenge = $this->makeChallenge();
        $tip = $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته متحرک']);

        $this->loginAsAdmin();

        $this->put(route('panel.tips.update', [$challenge, $tip]), [
            'day_number' => 2,
            'emoji' => '💡',
            'title' => 'نکته متحرک',
            'body' => '',
        ])->assertRedirect();

        $this->assertSame(2, $tip->refresh()->day_number);

        $this->put(route('panel.tips.update', [$challenge, $tip]), [
            'day_number' => '',
            'emoji' => '💡',
            'title' => 'نکته متحرک',
        ])->assertRedirect();

        $this->assertNull($tip->refresh()->day_number);
        $this->assertSame(1, $challenge->generalTips()->count());
    }

    public function test_the_excel_export_lists_tip_days(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته روز اول', 'body' => 'متن']);
        $challenge->tips()->create(['title' => 'نکته کلی']);

        $sheets = ChallengeExport::sheets($challenge);

        $this->assertArrayHasKey('نکته‌ها', $sheets);

        $flat = collect($sheets['نکته‌ها'])->flatten()->implode(' | ');

        $this->assertStringContainsString('نکته روز اول', $flat);
        $this->assertStringContainsString('بدون روز مشخص (کلی)', $flat);
        $this->assertStringContainsString('روز ۱', $flat);
    }

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش نکته‌ها',
            'slug' => 'tips-challenge',
            'is_published' => true,
            'daily_release' => false,
            'pledge_enabled' => false,
            'starts_on' => today()->subDays(2),
        ], $attributes));
    }
}
