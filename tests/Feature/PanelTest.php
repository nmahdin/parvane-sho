<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeTip;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_panel_requires_login(): void
    {
        $this->get(route('panel.dashboard'))->assertRedirect(route('panel.login'));
        $this->get(route('panel.challenges.index'))->assertRedirect(route('panel.login'));
    }

    public function test_login_works_with_username_and_password(): void
    {
        $this->makeSuperAdmin();

        $this->post(route('panel.login.attempt'), [
            'username' => 'admin',
            'password' => 'a12345678',
        ])->assertRedirect(route('panel.dashboard'));

        $this->get(route('panel.dashboard'))->assertOk()->assertSee('داشبورد');
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $this->makeSuperAdmin();

        $this->post(route('panel.login.attempt'), [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->post(route('panel.login.attempt'), [
            'username' => 'unknown-admin',
            'password' => 'a12345678',
        ])->assertSessionHasErrors('username');

        $this->get(route('panel.dashboard'))->assertRedirect(route('panel.login'));
    }

    public function test_login_needs_both_fields(): void
    {
        $this->makeSuperAdmin();

        $this->post(route('panel.login.attempt'), ['password' => 'a12345678'])
            ->assertSessionHasErrors('username');

        $this->post(route('panel.login.attempt'), ['username' => 'admin'])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_can_manage_challenges_and_participants(): void
    {
        $this->loginAsAdmin();

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

    public function test_admin_can_change_their_own_password(): void
    {
        $admin = $this->loginAsAdmin($this->superAdmin());

        $this->put(route('panel.settings.password'), [
            'current_password' => 'a12345678',
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-pass', $admin->fresh()->password));

        // رمز قدیمی دیگر کار نمی‌کند و رمز جدید وارد می‌کند
        $this->post(route('panel.logout'));

        $this->post(route('panel.login.attempt'), ['username' => 'admin', 'password' => 'a12345678'])
            ->assertSessionHasErrors('username');

        $this->post(route('panel.login.attempt'), ['username' => 'admin', 'password' => 'new-secret-pass'])
            ->assertRedirect(route('panel.dashboard'));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $admin = $this->loginAsAdmin($this->superAdmin());

        $this->put(route('panel.settings.password'), [
            'current_password' => 'not-my-password',
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('a12345678', $admin->fresh()->password));
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

    public function test_admin_can_add_tips_questions_and_files_to_a_challenge(): void
    {
        Storage::fake('local');

        $challenge = Challenge::create([
            'title' => 'چالش با جزئیات',
            'slug' => 'details',
            'is_published' => true,
        ]);

        $this->loginAsAdmin();

        // نکته
        $this->post(route('panel.tips.store', $challenge), [
            'emoji' => '💧',
            'title' => 'آب کافی بنوش',
            'body' => 'روزی ۶ لیوان',
        ])->assertRedirect();

        $this->assertSame(1, $challenge->tips()->count());

        // پرسش و پاسخ
        $this->post(route('panel.questions.store', $challenge), [
            'question' => 'اگر یک روز را از دست بدهم؟',
            'answer' => '**هیچ چیزی** از بین نمی‌رود.',
            'asked_by' => 'زهرا',
            'is_published' => '1',
        ])->assertRedirect();

        $question = ChallengeQuestion::query()->firstOrFail();

        // فایل ضمیمه
        $this->post(route('panel.attachments.store', $challenge), [
            'title' => 'راهنمای چالش',
            'file' => UploadedFile::fake()->createWithContent('guide.txt', 'محتوای راهنما'),
        ])->assertRedirect();

        $attachment = $challenge->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);

        // صفحه عمومی چالش همه‌چیز را نشان می‌دهد
        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('آب کافی بنوش')
            ->assertSee('اگر یک روز را از دست بدهم؟')
            ->assertSee('<strong', false)
            ->assertSee('راهنمای چالش');

        // دانلود فایل
        $this->get(route('challenge.files.show', [$challenge, $attachment]))
            ->assertOk()
            ->assertDownload('guide.txt');

        // پنهان کردن پرسش از دید مخاطب
        $this->patch(route('panel.questions.publish', [$challenge, $question]))->assertRedirect();

        $this->get(route('challenges.show', $challenge))->assertDontSee('اگر یک روز را از دست بدهم؟');

        // حذف نکته و فایل
        $this->delete(route('panel.tips.destroy', [$challenge, $challenge->tips()->first()]))->assertRedirect();
        $this->delete(route('panel.attachments.destroy', [$challenge, $attachment]))->assertRedirect();

        $this->assertSame(0, $challenge->tips()->count());
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_hidden_questions_are_not_shown_on_the_public_page(): void
    {
        $challenge = Challenge::create(['title' => 'چالش', 'slug' => 'qa', 'is_published' => true]);

        ChallengeTip::create(['challenge_id' => $challenge->id, 'title' => 'نکته نمایشی']);
        ChallengeQuestion::create(['challenge_id' => $challenge->id, 'question' => 'پرسش پنهان', 'is_published' => false]);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('نکته نمایشی')
            ->assertDontSee('پرسش پنهان');
    }

    public function test_participants_get_short_codes_and_can_log_in_with_them(): void
    {
        $challenge = Challenge::create(['title' => 'چالش کوتاه', 'slug' => 'short', 'is_published' => true]);

        $this->loginAsAdmin();

        $this->post(route('panel.participants.store', $challenge), ['names' => 'مینا شریفی'])->assertRedirect();

        $participant = Participant::query()->firstOrFail();

        $this->assertSame(Participant::TOKEN_LENGTH, mb_strlen($participant->token));
        $this->assertSame(route('participant.show', $participant), url('/p/'.$participant->token));

        // ورود با کد
        $this->post(route('participant.lookup.attempt'), ['code' => $participant->token])
            ->assertRedirect(route('participant.show', $participant));

        // چسباندن کل لینک هم کار می‌کند
        $this->post(route('participant.lookup.attempt'), ['code' => url('/p/'.$participant->token)])
            ->assertRedirect(route('participant.show', $participant));

        // کد نامعتبر
        $this->post(route('participant.lookup.attempt'), ['code' => 'abcdefgh'])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_sees_a_participant_status_page(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش وضعیت',
            'slug' => 'status',
            'is_published' => true,
            'starts_on' => today()->subDays(2),
            'ends_on' => today()->addDays(3),
        ]);

        $task = $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);

        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'سارا']);
        $participant->completions()->create(['challenge_task_id' => $task->id, 'completed_at' => now()]);

        $this->loginAsAdmin();

        $this->get(route('panel.participants.show', $participant))
            ->assertOk()
            ->assertSee('وضعیت سارا')
            ->assertSee('وضعیت روز به روز')
            ->assertSee('فعالیت ۱۴ روز اخیر');

        // فیلترها و مرتب‌سازی فهرست هم کار می‌کنند
        $this->get(route('panel.participants.index', ['status' => 'today', 'sort' => 'weakest']))
            ->assertOk()
            ->assertSee('سارا');

        $this->get(route('panel.participants.index', ['status' => 'notstarted', 'sort' => 'name']))
            ->assertOk()
            ->assertDontSee('سارا');
    }
}
