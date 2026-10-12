<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['panel.password' => 'secret-panel-pass']);
    }

    public function test_panel_requires_the_password(): void
    {
        $this->get(route('panel.dashboard'))->assertRedirect(route('panel.login'));
        $this->get(route('panel.challenges.index'))->assertRedirect(route('panel.login'));
    }

    public function test_login_works_with_password_only(): void
    {
        $this->post(route('panel.login.attempt'), ['password' => 'secret-panel-pass'])
            ->assertRedirect(route('panel.dashboard'));

        $this->get(route('panel.dashboard'))->assertOk()->assertSee('داشبورد');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->post(route('panel.login.attempt'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->get(route('panel.dashboard'))->assertRedirect(route('panel.login'));
    }

    public function test_admin_can_manage_challenges_and_participants(): void
    {
        $this->post(route('panel.login.attempt'), ['password' => 'secret-panel-pass']);

        // ساخت چالش
        $this->post(route('panel.challenges.store'), [
            'title' => 'چالش تست',
            'tagline' => 'توضیح کوتاه',
            'starts_on' => '۱۴۰۵/۰۷/۲۰',
            'is_published' => '1',
        ])->assertRedirect();

        $challenge = Challenge::query()->firstOrFail();
        $this->assertSame('2026-10-12', $challenge->starts_on->toDateString());

        // افزودن کارها به صورت گروهی
        $this->post(route('panel.tasks.bulk', $challenge), [
            'day_number' => 1,
            'items' => "کار اول\n کار دوم ",
        ])->assertRedirect();

        $this->assertSame(2, $challenge->tasks()->count());

        // افزودن شرکت‌کننده‌ها
        $this->post(route('panel.participants.store', $challenge), [
            'names' => "زهرا محمدی\nامیر رضایی",
        ])->assertRedirect();

        $this->assertSame(2, $challenge->participants()->count());

        $participant = $challenge->participants()->first();
        $this->assertNotEmpty($participant->token);

        // لینک اختصاصی ساخته می‌شود و بدون ورود کار می‌کند
        $this->get(route('participant.show', $participant))->assertOk()->assertSee($participant->name);

        // بازتولید لینک
        $oldToken = $participant->token;
        $this->patch(route('panel.participants.regenerate', $participant))->assertRedirect();
        $this->assertNotSame($oldToken, $participant->fresh()->token);
    }

    public function test_admin_can_change_the_panel_password(): void
    {
        $this->post(route('panel.login.attempt'), ['password' => 'secret-panel-pass']);

        $this->put(route('panel.settings.password'), [
            'current_password' => 'secret-panel-pass',
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-pass', Setting::panelPasswordHash()));

        // رمز قدیمی دیگر کار نمی‌کند و رمز جدید وارد می‌کند
        $this->post(route('panel.logout'));

        $this->post(route('panel.login.attempt'), ['password' => 'secret-panel-pass'])
            ->assertSessionHasErrors('password');

        $this->post(route('panel.login.attempt'), ['password' => 'new-secret-pass'])
            ->assertRedirect(route('panel.dashboard'));
    }

    public function test_participant_dashboard_can_be_opened_without_login(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش عمومی',
            'slug' => 'public-challenge',
            'is_published' => true,
        ]);

        $task = $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار نمونه']);

        $participant = Participant::create([
            'challenge_id' => $challenge->id,
            'name' => 'سارا',
        ]);

        $this->get(route('participant.show', $participant))->assertOk()->assertSee('سارا');

        $this->post(route('participant.tasks.toggle', [$participant, $task]))->assertRedirect();
        $this->assertDatabaseHas('task_completions', [
            'participant_id' => $participant->id,
            'challenge_task_id' => $task->id,
        ]);

        // تیک دوباره برداشته می‌شود
        $this->post(route('participant.tasks.toggle', [$participant, $task]))->assertRedirect();
        $this->assertDatabaseMissing('task_completions', [
            'participant_id' => $participant->id,
            'challenge_task_id' => $task->id,
        ]);

        // لینک نامعتبر
        $this->get('/p/'.str_repeat('z', 16))->assertNotFound();
    }
}
