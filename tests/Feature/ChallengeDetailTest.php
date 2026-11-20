<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Challenge;
use App\Models\ChallengeTask;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحه «مدیریت چالش» در پنل: فرم افزودن شرکت‌کننده بدون شماره تماس،
 * منوی بازشوی وضعیت هر کار و سایدبار بدون نام و نقش کاربر وارد‌شده.
 */
class ChallengeDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Challenge, 1: ChallengeTask, 2: Participant, 3: Participant}
     */
    private function makeChallenge(Admin $admin, string $slug = 'detail-challenge'): array
    {
        $challenge = Challenge::create([
            'admin_id' => $admin->getKey(),
            'title' => 'چالش جزئیات',
            'slug' => $slug,
            'is_published' => true,
            'starts_on' => today(),
        ]);

        $task = $challenge->tasks()->create(['day_number' => 1, 'title' => 'نوشتن سه نعمت']);

        $zahra = $challenge->participants()->create(['name' => 'زهرا محمدی']);
        $amir = $challenge->participants()->create(['name' => 'امیر رضایی']);

        $zahra->toggleTask($task);

        return [$challenge, $task, $zahra, $amir];
    }

    /**
     * فقط HTML سایدبار پنل.
     */
    private function sidebarOf(string $html): string
    {
        $start = strpos($html, '<aside');
        $end = strpos($html, '</aside>');

        $this->assertNotFalse($start, 'سایدبار پنل پیدا نشد.');
        $this->assertNotFalse($end, 'پایان سایدبار پنل پیدا نشد.');

        return substr($html, $start, $end - $start);
    }

    public function test_the_add_participant_form_has_no_phone_number_field(): void
    {
        $admin = $this->superAdmin();
        [$challenge] = $this->makeChallenge($admin);

        $this->loginAsAdmin($admin);

        $response = $this->get(route('panel.challenges.show', $challenge))->assertOk();

        $response->assertDontSee('name="contact"', false);
        $response->assertDontSee('شماره تماس');
        $response->assertDontSee('راه ارتباطی');

        // فرم هنوز کار می‌کند: فقط نام‌ها فرستاده می‌شود
        $this->post(route('panel.participants.store', $challenge), ['names' => "نگار کریمی\nسارا احمدی"])
            ->assertRedirect(route('panel.challenges.show', $challenge));

        $this->assertSame(4, $challenge->participants()->count());
    }

    public function test_each_task_shows_who_has_done_it_and_who_has_not(): void
    {
        $admin = $this->superAdmin();
        [$challenge, $task, $zahra, $amir] = $this->makeChallenge($admin);

        $this->loginAsAdmin($admin);

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        // منوی بازشو برای هر کار
        $this->assertStringContainsString('چه کسانی انجام داده‌اند؟', $html);
        $this->assertStringContainsString('انجام داده‌اند (۱)', $html);
        $this->assertStringContainsString('انجام نداده‌اند (۱)', $html);

        // بلوک «انجام داده‌اند» نام زهرا و بلوک «انجام نداده‌اند» نام امیر را دارد
        $start = strpos($html, 'انجام داده‌اند (');
        $middle = strpos($html, 'انجام نداده‌اند (', $start);
        $end = strpos($html, '</details>', $middle);

        $this->assertNotFalse($start);
        $this->assertNotFalse($middle);
        $this->assertNotFalse($end);

        $doneBlock = substr($html, $start, $middle - $start);
        $pendingBlock = substr($html, $middle, $end - $middle);

        $this->assertStringContainsString($zahra->name, $doneBlock);
        $this->assertStringNotContainsString($amir->name, $doneBlock);
        $this->assertStringContainsString($amir->name, $pendingBlock);
        $this->assertStringNotContainsString($zahra->name, $pendingBlock);

        // و تیک‌زدن همان کار را برای امیر هم نشان می‌دهد
        $amir->toggleTask($task);

        $html = $this->get(route('panel.challenges.show', $challenge))->getContent();

        $this->assertStringContainsString('انجام داده‌اند (۲)', $html);
        $this->assertStringContainsString('انجام نداده‌اند (۰)', $html);
        $this->assertStringContainsString('همهٔ شرکت‌کننده‌ها این کار را انجام داده‌اند.', $html);
    }

    public function test_the_sidebar_does_not_show_the_logged_in_user_name_or_role(): void
    {
        $admin = $this->makeSuperAdmin(['name' => 'مریم احمدی', 'username' => 'maryam-export']);

        $this->loginAsAdmin($admin);

        $html = $this->get(route('panel.dashboard'))->assertOk()->getContent();
        $sidebar = $this->sidebarOf($html);

        $this->assertStringNotContainsString('مریم احمدی', $sidebar);
        $this->assertStringNotContainsString('maryam-export', $sidebar);
        $this->assertStringNotContainsString($admin->roleLabel(), $sidebar);

        // ولی دکمه‌های مهم سایدبار سر جای خودشان هستند
        $this->assertStringContainsString('خروج از پنل', $sidebar);
        $this->assertStringContainsString('مشاهده سایت', $sidebar);

        // مدیر چالش هم همین‌طور
        $manager = $this->makeChallengeAdmin(['name' => 'سعید کریمی', 'username' => 'saeed-sidebar']);
        $this->loginAsAdmin($manager);

        $sidebar = $this->sidebarOf($this->get(route('panel.challenges.index'))->assertOk()->getContent());

        $this->assertStringNotContainsString('سعید کریمی', $sidebar);
        $this->assertStringNotContainsString($manager->roleLabel(), $sidebar);
    }
}
