<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeTask;
use App\Models\Participant;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش روزانه',
            'slug' => 'daily',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today()->subDays(1),
        ], $attributes));
    }

    public function test_only_the_current_and_past_days_are_unlocked(): void
    {
        $challenge = $this->makeChallenge();

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز دوم']);
        $challenge->tasks()->create(['day_number' => 3, 'title' => 'کار روز سوم']);

        $this->assertTrue($challenge->isDayUnlocked(1));
        $this->assertTrue($challenge->isDayUnlocked(2));
        $this->assertFalse($challenge->isDayUnlocked(3));

        $this->assertSame(2, $challenge->currentDayNumber());
        $this->assertSame(1, $challenge->lockedDaysCount());
        $this->assertSame(today()->addDay()->toDateString(), $challenge->nextUnlockDate()?->toDateString());
    }

    public function test_locked_days_are_hidden_on_the_public_page(): void
    {
        // چالش از امروز شروع می‌شود، پس روز دوم هنوز قفل است.
        $challenge = $this->makeChallenge(['starts_on' => today()]);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار امروز']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار فردا']);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('کار امروز')
            ->assertDontSee('کار فردا')
            ->assertSee('هنوز آزاد نشده');
    }

    public function test_participant_cannot_tick_a_locked_day(): void
    {
        $challenge = $this->makeChallenge();

        $openTask = $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار امروز']);
        $lockedTask = $challenge->tasks()->create(['day_number' => 3, 'title' => 'کار فردا']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        // روز باز: تیک می‌خورد
        $this->post(route('participant.tasks.toggle', [$participant, $openTask]))
            ->assertRedirect(route('participant.show', $participant).'#task-'.$openTask->id);

        $this->assertDatabaseHas('task_completions', [
            'participant_id' => $participant->id,
            'challenge_task_id' => $openTask->id,
        ]);

        // روز قفل: تیک نمی‌خورد و پیام هشدار برمی‌گردد
        $this->post(route('participant.tasks.toggle', [$participant, $lockedTask]))
            ->assertRedirect(route('participant.show', $participant).'#day-3')
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('task_completions', [
            'participant_id' => $participant->id,
            'challenge_task_id' => $lockedTask->id,
        ]);
    }

    public function test_participant_dashboard_shows_locked_days_without_tasks(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار دیروز']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار امروز']);
        $challenge->tasks()->create(['day_number' => 3, 'title' => 'کار فردا']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('کار امروز')
            ->assertDontSee('کار فردا')
            ->assertSee('این روز هنوز باز نشده است')
            ->assertSee('روزهای قفل‌شده');
    }

    public function test_disabling_daily_release_opens_all_days(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);
        $challenge->tasks()->create(['day_number' => 5, 'title' => 'کار روز پنجم']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->assertTrue($challenge->isDayUnlocked(5));
        $this->assertSame(0, $challenge->lockedDaysCount());

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('کار روز پنجم');
    }

    public function test_admin_can_toggle_daily_release_when_saving_a_challenge(): void
    {

        $this->loginAsAdmin();

        $challenge = $this->makeChallenge(['slug' => 'toggle-me']);

        // خاموش کردن آزادسازی روزبه‌روز
        $this->put(route('panel.challenges.update', $challenge), [
            'title' => $challenge->title,
            'starts_on' => Jalali::inputValue($challenge->starts_on),
        ])->assertRedirect();

        $this->assertFalse($challenge->fresh()->daily_release);

        // روشن کردن دوباره
        $this->put(route('panel.challenges.update', $challenge), [
            'title' => $challenge->title,
            'daily_release' => '1',
        ])->assertRedirect();

        $this->assertTrue($challenge->fresh()->daily_release);
    }

    public function test_challenges_without_a_start_date_count_from_their_creation_day(): void
    {
        $challenge = Challenge::create([
            'title' => 'بدون تاریخ',
            'slug' => 'no-date',
            'is_published' => true,
            'daily_release' => true,
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار اول']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار دوم']);

        $this->assertTrue($challenge->isDayUnlocked(1));
        $this->assertFalse($challenge->isDayUnlocked(2));
    }

    public function test_the_palette_accents_all_resolve_to_style_classes(): void
    {
        foreach (array_keys(Challenge::ACCENTS) as $accent) {
            $challenge = new Challenge(['accent' => $accent]);

            $this->assertSame($accent, $challenge->accentKey());
            $this->assertNotEmpty($challenge->styleValue('gradient'));
            $this->assertNotEmpty($challenge->styleValue('bar'));
        }

        // رنگ‌های چالش‌های قدیمی هم به پالت جدید نگاشت می‌شوند
        $this->assertSame('jade', (new Challenge(['accent' => 'violet']))->accentKey());
        $this->assertSame('gold', (new Challenge(['accent' => 'amber']))->accentKey());
        $this->assertSame('leaf', (new Challenge(['accent' => 'emerald']))->accentKey());
        $this->assertSame('jade', (new Challenge(['accent' => 'unknown']))->accentKey());
    }

    public function test_task_belonging_to_another_challenge_cannot_be_toggled(): void
    {
        $challenge = $this->makeChallenge();
        $other = $this->makeChallenge(['slug' => 'other', 'title' => 'چالش دیگر']);

        $foreignTask = $other->tasks()->create(['day_number' => 1, 'title' => 'کار چالش دیگر']);
        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);

        $this->post(route('participant.tasks.toggle', [$participant, $foreignTask]))->assertNotFound();
    }

    public function test_locked_task_helper_marks_future_tasks(): void
    {
        $challenge = $this->makeChallenge();

        $openTask = $challenge->tasks()->create(['day_number' => 1, 'title' => 'امروز']);
        $lockedTask = $challenge->tasks()->create(['day_number' => 4, 'title' => 'آینده']);

        $this->assertFalse($challenge->isTaskLocked($openTask));
        $this->assertTrue($challenge->isTaskLocked($lockedTask));

        $this->assertInstanceOf(ChallengeTask::class, $lockedTask);
        $this->assertNotNull($challenge->unlockLabel(4));
    }
}
