<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تعهدنامه: تنظیمش هنگام ساخت/ویرایش چالش، ثبتش در اولین ورود شرکت‌کننده
 * و بسته بودن کارهای چالش تا وقتی تعهدنامه ثبت نشده است.
 */
class PledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_panel_saves_the_pledge_settings_when_creating_a_challenge(): void
    {
        $this->loginAsAdmin();

        $this->post(route('panel.challenges.store'), [
            'title' => 'چالش با تعهد',
            'slug' => 'pledge-challenge',
            'pledge_enabled' => '1',
            'pledge_title' => 'تعهدنامهٔ من',
            'pledge_text' => "- هر روز یک قدم\n- رها نکردن",
            'is_published' => '1',
        ])->assertRedirect();

        $challenge = Challenge::query()->where('slug', 'pledge-challenge')->firstOrFail();

        $this->assertTrue($challenge->pledgeEnabled());
        $this->assertSame('تعهدنامهٔ من', $challenge->pledgeTitle());
        $this->assertStringContainsString('هر روز یک قدم', $challenge->pledgeText());
    }

    public function test_the_pledge_settings_can_be_edited_and_turned_off(): void
    {
        $challenge = $this->makeChallenge([
            'pledge_enabled' => true,
            'pledge_title' => 'عنوان قدیمی',
            'pledge_text' => 'متن قدیمی',
        ]);

        $this->loginAsAdmin();

        $this->put(route('panel.challenges.update', $challenge), [
            'title' => $challenge->title,
            'slug' => $challenge->slug,
            'pledge_title' => 'عنوان تازه',
            'pledge_text' => 'متن تازه',
            'is_published' => '1',
            // تیک تعهدنامه برداشته شده
        ])->assertRedirect();

        $challenge->refresh();

        $this->assertFalse($challenge->pledgeEnabled());
        $this->assertSame('عنوان تازه', $challenge->pledge_title);

        // صفحه ویرایش هم همان مقادیر را نشان می‌دهد
        $this->get(route('panel.challenges.edit', $challenge))
            ->assertOk()
            ->assertSee('عنوان تازه')
            ->assertSee('تعهدنامه ورود شرکت‌کننده‌ها');
    }

    public function test_the_participant_must_accept_the_pledge_on_the_first_visit(): void
    {
        $challenge = $this->makeChallenge([
            'pledge_enabled' => true,
            'pledge_title' => 'تعهدنامهٔ آزمون',
            'pledge_text' => 'متن تعهد آزمون',
        ]);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $response = $this->get(route('participant.show', $participant))->assertOk();

        $response->assertSee('تعهدنامهٔ آزمون');
        $response->assertSee('متن تعهد آزمون');
        $response->assertSee('تعهدنامه را ثبت می‌کنم و شروع می‌کنم');

        // تا تعهدنامه ثبت نشده، کارهای چالش نمایش داده نمی‌شوند
        $response->assertDontSee('کار روز اول');
        $this->assertNull($participant->refresh()->pledged_at);
    }

    public function test_accepting_the_pledge_opens_the_dashboard(): void
    {
        [$challenge, $participant] = $this->pledgeScenario();

        // بدون تیک، ثبت نمی‌شود
        $this->from(route('participant.show', $participant))
            ->post(route('participant.pledge', $participant), [])
            ->assertSessionHasErrors('accept');

        $this->assertNull($participant->refresh()->pledged_at);

        $this->post(route('participant.pledge', $participant), ['accept' => '1'])
            ->assertRedirect(route('participant.show', $participant));

        $this->assertNotNull($participant->refresh()->pledged_at);
        $this->assertTrue($participant->hasPledged());

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('کار روز اول')
            ->assertDontSee('تعهدنامه را ثبت می‌کنم و شروع می‌کنم');
    }

    public function test_tasks_cannot_be_ticked_before_the_pledge(): void
    {
        [$challenge, $participant] = $this->pledgeScenario();

        $task = $challenge->tasks()->firstOrFail();

        $this->from(route('participant.show', $participant))
            ->post(route('participant.tasks.toggle', [$participant, $task]))
            ->assertRedirect();

        $this->assertSame(0, $participant->refresh()->completedCount());
        $this->assertFalse($participant->isTaskDone($task));
    }

    public function test_a_challenge_without_pledge_opens_directly(): void
    {
        $challenge = $this->makeChallenge(['pledge_enabled' => false]);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار آزاد']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('کار آزاد')
            ->assertDontSee('تعهدنامه را ثبت می‌کنم و شروع می‌کنم');
    }

    public function test_the_admin_sees_who_has_signed_the_pledge(): void
    {
        [$challenge, $participant] = $this->pledgeScenario();

        $this->loginAsAdmin();

        $this->get(route('panel.participants.show', $participant))
            ->assertOk()
            ->assertSee('تعهدنامه ثبت نشده');

        $participant->markPledged();

        $this->get(route('panel.participants.show', $participant->refresh()))
            ->assertOk()
            ->assertSee('تعهدنامه ثبت شده');

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('تعهدنامه: فعال', false)
            ->assertSee('تعهدنامه ✓');
    }

    /**
     * @return array{0: Challenge, 1: Participant}
     */
    private function pledgeScenario(): array
    {
        $challenge = $this->makeChallenge([
            'pledge_enabled' => true,
            'pledge_title' => 'تعهدنامه آزمون',
            'pledge_text' => 'متن تعهد',
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);

        return [$challenge, Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا'])];
    }

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش تعهد',
            'slug' => 'pledge',
            'is_published' => true,
            'daily_release' => false,
            'starts_on' => today()->subDays(2),
        ], $attributes));
    }
}
