<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Challenge;
use App\Models\ChallengeQuestionAnswer;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * چند مدیر: مدیر اصلی همه چیز را می‌بیند، هر مدیر چالش فقط چالش‌ها و
 * شرکت‌کننده‌های خودش را.
 */
class MultiAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallengeFor(Admin $admin, array $attributes = []): Challenge
    {
        $number = Challenge::query()->count() + 1;

        return Challenge::create(array_merge([
            'admin_id' => $admin->getKey(),
            'title' => 'چالش '.$admin->name,
            'slug' => 'challenge-'.$number,
            'is_published' => true,
            'starts_on' => today(),
        ], $attributes));
    }

    public function test_super_admin_sees_every_challenge_and_participant(): void
    {
        $super = $this->superAdmin();
        $manager = $this->makeChallengeAdmin();

        $own = $this->makeChallengeFor($manager, ['title' => 'چالش مدیر دوم']);
        $own->participants()->create(['name' => 'زهرا از چالش دوم']);

        $superChallenge = $this->makeChallengeFor($super, ['title' => 'چالش مدیر اصلی']);
        $superChallenge->participants()->create(['name' => 'امیر از چالش اصلی']);

        $this->loginAsAdmin($super);

        $this->get(route('panel.challenges.index'))
            ->assertOk()
            ->assertSee('چالش مدیر دوم')
            ->assertSee('چالش مدیر اصلی');

        $this->get(route('panel.participants.index'))
            ->assertOk()
            ->assertSee('زهرا از چالش دوم')
            ->assertSee('امیر از چالش اصلی');

        $this->get(route('panel.challenges.show', $own))->assertOk();

        $this->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('چالش مدیر دوم');
    }

    public function test_challenge_admin_only_sees_their_own_challenges_and_participants(): void
    {
        $super = $this->superAdmin();
        $mine = $this->makeChallengeAdmin();
        $other = $this->makeChallengeAdmin(['name' => 'مدیر دیگر', 'username' => 'other']);

        $myChallenge = $this->makeChallengeFor($mine, ['title' => 'چالش خودم']);
        $myChallenge->participants()->create(['name' => 'شرکت‌کننده من']);

        $otherChallenge = $this->makeChallengeFor($other, ['title' => 'چالش دیگران']);
        $otherChallenge->participants()->create(['name' => 'شرکت‌کننده دیگران']);

        $this->loginAsAdmin($mine);

        $this->get(route('panel.challenges.index'))
            ->assertOk()
            ->assertSee('چالش خودم')
            ->assertDontSee('چالش دیگران');

        $this->get(route('panel.participants.index'))
            ->assertOk()
            ->assertSee('شرکت‌کننده من')
            ->assertDontSee('شرکت‌کننده دیگران');

        $this->get(route('panel.dashboard'))
            ->assertOk()
            ->assertDontSee('چالش دیگران');

        // و به چالش شرکت‌کننده‌های خودش دسترسی دارد
        $this->get(route('panel.challenges.show', $myChallenge))->assertOk();
        $this->get(route('panel.participants.show', $myChallenge->participants()->first()))->assertOk();
    }

    public function test_challenge_admin_gets_404_on_another_admins_data(): void
    {
        $other = $this->makeChallengeAdmin(['name' => 'مدیر دیگر', 'username' => 'other']);
        $otherChallenge = $this->makeChallengeFor($other, ['title' => 'چالش دیگران']);
        $otherParticipant = $otherChallenge->participants()->create(['name' => 'شرکت‌کننده دیگران']);
        $otherTask = $otherChallenge->tasks()->create(['day_number' => 1, 'title' => 'کار دیگران']);

        $this->loginAsAdmin($this->makeChallengeAdmin());

        $this->get(route('panel.challenges.show', $otherChallenge))->assertNotFound();
        $this->get(route('panel.challenges.edit', $otherChallenge))->assertNotFound();
        $this->put(route('panel.challenges.update', $otherChallenge), ['title' => 'دستکاری'])->assertNotFound();
        $this->delete(route('panel.challenges.destroy', $otherChallenge))->assertNotFound();

        $this->post(route('panel.days.store', $otherChallenge), [
            'type' => 'task',
            'title' => 'کار جدید',
        ])->assertNotFound();

        $this->post(route('panel.tasks.bulk', $otherChallenge), ['items' => 'کار'])->assertNotFound();

        $this->post(route('panel.participants.store', $otherChallenge), ['names' => 'نفر جدید'])->assertNotFound();

        $this->get(route('panel.participants.show', $otherParticipant))->assertNotFound();
        $this->put(route('panel.participants.update', $otherParticipant), ['name' => 'دستکاری'])->assertNotFound();
        $this->delete(route('panel.participants.destroy', $otherParticipant))->assertNotFound();
        $this->patch(route('panel.participants.regenerate', $otherParticipant))->assertNotFound();

        // هیچ‌کدام از این درخواست‌ها داده‌ای عوض نکرده‌اند
        $this->assertSame('چالش دیگران', $otherChallenge->fresh()->title);
        $this->assertSame('شرکت‌کننده دیگران', $otherParticipant->fresh()->name);
        $this->assertSame(1, $otherChallenge->tasks()->count());
        $this->assertSame(1, $otherChallenge->participants()->count());
        $this->assertSame('کار دیگران', $otherTask->fresh()->title);
    }

    public function test_each_admin_can_open_the_challenge_forms(): void
    {
        $manager = $this->makeChallengeAdmin();
        $challenge = $this->makeChallengeFor($manager, ['title' => 'چالش فرم‌ها']);

        $this->loginAsAdmin($manager);

        // فرم ساخت چالش برای مدیر چالش هم باز می‌شود (بدون فیلد انتخاب مدیر)
        $this->get(route('panel.challenges.create'))
            ->assertOk()
            ->assertDontSee('مدیر این چالش');

        $this->get(route('panel.challenges.edit', $challenge))
            ->assertOk()
            ->assertSee('چالش فرم‌ها')
            ->assertDontSee('مدیر این چالش');

        // مدیر اصلی در همان فرم، فیلد انتخاب مدیر را می‌بیند
        $this->post(route('panel.logout'));
        $this->loginAsAdmin($this->superAdmin());

        $this->get(route('panel.challenges.edit', $challenge))
            ->assertOk()
            ->assertSee('مدیر این چالش');
    }

    public function test_admin_management_and_site_settings_are_super_only(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin());

        $this->get(route('panel.admins.index'))->assertForbidden();
        $this->post(route('panel.admins.store'), [
            'name' => 'نفوذی',
            'username' => 'intruder',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])->assertForbidden();
        $this->put(route('panel.settings.update'), ['site_name' => 'دستکاری'])->assertForbidden();

        $this->assertDatabaseMissing('admins', ['username' => 'intruder']);
        $this->assertSame('پروانه شو', Setting::get('site_name', config('panel.site_name')));
    }

    public function test_super_admin_can_create_an_admin_who_can_log_in(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        $this->post(route('panel.admins.store'), [
            'name' => 'مریم احمدی',
            'username' => 'maryam',
            'password' => 'maryam-pass',
            'password_confirmation' => 'maryam-pass',
        ])->assertRedirect(route('panel.admins.index'));

        $this->assertDatabaseHas('admins', ['username' => 'maryam', 'is_super' => false]);

        // خروج مدیر اصلی و ورود مدیر جدید
        $this->post(route('panel.logout'));

        $this->post(route('panel.login.attempt'), ['username' => 'maryam', 'password' => 'maryam-pass'])
            ->assertRedirect(route('panel.dashboard'));

        $this->get(route('panel.dashboard'))->assertOk();
        $this->get(route('panel.admins.index'))->assertForbidden();
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        $this->post(route('panel.admins.store'), [
            'name' => 'مدیر تکراری',
            'username' => 'admin',
            'password' => 'another-pass',
            'password_confirmation' => 'another-pass',
        ])->assertSessionHasErrors('username');
    }

    public function test_super_admin_can_assign_a_challenge_to_another_admin(): void
    {
        $super = $this->superAdmin();
        $manager = $this->makeChallengeAdmin();

        $challenge = $this->makeChallengeFor($super, ['title' => 'چالش واگذارشدنی']);

        $this->loginAsAdmin($super);

        $this->put(route('panel.challenges.update', $challenge), [
            'title' => 'چالش واگذارشدنی',
            'admin_id' => $manager->getKey(),
        ])->assertRedirect();

        $this->assertSame($manager->getKey(), $challenge->fresh()->admin_id);

        // از این پس فقط مدیر جدید آن را می‌بیند
        $this->post(route('panel.logout'));
        $this->loginAsAdmin($manager);

        $this->get(route('panel.challenges.show', $challenge))->assertOk();
        $this->get(route('panel.challenges.index'))->assertOk()->assertSee('چالش واگذارشدنی');
    }

    public function test_inactive_admin_cannot_log_in(): void
    {
        $this->makeChallengeAdmin(['is_active' => false]);

        $this->post(route('panel.login.attempt'), ['username' => 'manager', 'password' => 'manager-pass'])
            ->assertSessionHasErrors('username');

        $this->get(route('panel.dashboard'))->assertRedirect(route('panel.login'));
    }

    public function test_super_admin_cannot_disable_or_delete_themselves(): void
    {
        $super = $this->loginAsAdmin($this->superAdmin());

        $this->patch(route('panel.admins.toggle', $super))->assertSessionHas('error');
        $this->delete(route('panel.admins.destroy', $super))->assertSessionHas('error');

        $this->assertTrue($super->fresh()->is_active);
        $this->assertDatabaseHas('admins', ['id' => $super->getKey()]);
    }

    public function test_deleting_an_admin_moves_their_challenges_to_the_super_admin(): void
    {
        $super = $this->superAdmin();
        $manager = $this->makeChallengeAdmin();

        $challenge = $this->makeChallengeFor($manager, ['title' => 'چالش مدیر حذف‌شده']);
        $challenge->participants()->create(['name' => 'شرکت‌کننده باقی‌مانده']);

        $this->loginAsAdmin($super);

        $this->delete(route('panel.admins.destroy', $manager))->assertRedirect(route('panel.admins.index'));

        $this->assertDatabaseMissing('admins', ['id' => $manager->getKey()]);
        $this->assertSame($super->getKey(), $challenge->fresh()->admin_id);
        $this->assertSame(1, $challenge->participants()->count());
    }

    public function test_super_admin_can_reset_an_admins_password(): void
    {
        $super = $this->superAdmin();
        $manager = $this->makeChallengeAdmin();

        $this->loginAsAdmin($super);

        $this->put(route('panel.admins.update', $manager), [
            'name' => $manager->name,
            'username' => $manager->username,
            'password' => 'reset-by-super',
            'password_confirmation' => 'reset-by-super',
        ])->assertRedirect(route('panel.admins.index'));

        $this->post(route('panel.logout'));

        $this->post(route('panel.login.attempt'), ['username' => 'manager', 'password' => 'reset-by-super'])
            ->assertRedirect(route('panel.dashboard'));
    }

    public function test_dashboard_answers_are_scoped_to_the_admin(): void
    {
        $super = $this->superAdmin();
        $mine = $this->makeChallengeAdmin();
        $other = $this->makeChallengeAdmin(['name' => 'مدیر دیگر', 'username' => 'other']);

        $myChallenge = $this->makeChallengeFor($mine, ['title' => 'چالش خودم']);
        $myQuestion = $myChallenge->questions()->create(['day_number' => 1, 'question' => 'پرسش من']);
        $myParticipant = $myChallenge->participants()->create(['name' => 'شرکت‌کننده من']);
        ChallengeQuestionAnswer::create([
            'challenge_question_id' => $myQuestion->getKey(),
            'participant_id' => $myParticipant->getKey(),
            'body' => 'پاسخ در چالش خودم',
        ]);

        $otherChallenge = $this->makeChallengeFor($other, ['title' => 'چالش دیگران']);
        $otherQuestion = $otherChallenge->questions()->create(['day_number' => 1, 'question' => 'پرسش دیگران']);
        $otherParticipant = $otherChallenge->participants()->create(['name' => 'شرکت‌کننده دیگران']);
        ChallengeQuestionAnswer::create([
            'challenge_question_id' => $otherQuestion->getKey(),
            'participant_id' => $otherParticipant->getKey(),
            'body' => 'پاسخ محرمانه دیگران',
        ]);

        $this->loginAsAdmin($mine);

        $this->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('پاسخ در چالش خودم')
            ->assertDontSee('پاسخ محرمانه دیگران');

        // مدیر اصلی همه پاسخ‌ها را می‌بیند
        $this->post(route('panel.logout'));
        $this->loginAsAdmin($super);

        $this->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('پاسخ در چالش خودم')
            ->assertSee('پاسخ محرمانه دیگران');
    }
}
