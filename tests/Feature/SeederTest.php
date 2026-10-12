<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Challenge;
use App\Models\ChallengeQuestionAnswer;
use App\Models\Participant;
use App\Models\Setting;
use App\Models\TaskCompletion;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoChallengeSeeder;
use Database\Seeders\DemoParticipantsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * سیدرها: فایل‌های جدا برای تنظیمات، چالش نمونه و شرکت‌کننده‌های نمونه؛
 * روی دیتابیس خالی داده واقعی می‌سازند و اجرای دوباره چیزی را تکرار نمی‌کند.
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_files_are_in_place(): void
    {
        foreach (['DatabaseSeeder', 'AdminSeeder', 'SettingsSeeder', 'DemoChallengeSeeder', 'DemoParticipantsSeeder'] as $class) {
            $file = database_path('seeders/'.$class.'.php');

            $this->assertFileExists($file, 'فایل سیدر '.$class.'.php وجود ندارد.');
            $this->assertTrue(class_exists('Database\\Seeders\\'.$class), 'کلاس سیدر '.$class.' پیدا نشد.');
        }
    }

    public function test_a_fresh_seed_builds_the_demo_data_with_real_numbers(): void
    {
        $this->seed(DatabaseSeeder::class);

        // تنظیمات سایت
        $this->assertSame(config('panel.site_name'), Setting::query()->where('key', 'site_name')->value('value'));

        $challenge = Challenge::query()->where('slug', DemoChallengeSeeder::SLUG)->firstOrFail();

        $this->assertSame(14, $challenge->tasks()->count(), 'هر روز چالش نمونه دو کار دارد.');
        $this->assertSame(3, $challenge->questions()->count());
        // سه نکته کلی + یک نکته مخصوص هر روز
        $this->assertSame(10, $challenge->tips()->count());
        $this->assertSame(3, $challenge->generalTips()->count());
        $this->assertSame(7, $challenge->tips()->whereNotNull('day_number')->count());
        $this->assertSame(1, $challenge->tipsForDay(1)->count());
        $this->assertSame(0, $challenge->tipsForDay(99)->count());
        $this->assertSame(1, $challenge->attachments()->count());
        $this->assertSame(3, $challenge->participants()->count());

        // مدیران هم از سیدر می‌آیند: مدیر اصلی + دو مدیر چالش نمونه
        $this->assertSame(3, Admin::query()->count());
        $this->assertTrue(Admin::query()->where('username', 'admin')->firstOrFail()->isSuper());
        $this->assertFalse(Admin::query()->where('username', 'maryam')->firstOrFail()->isSuper());
        $this->assertTrue(Admin::query()->where('username', 'sara')->exists());

        // چالش نمونه تعهدنامه دارد
        $this->assertTrue($challenge->pledgeEnabled());
        $this->assertNotSame('', $challenge->pledgeTitle());
        $this->assertStringContainsString('تعهد', $challenge->pledgeText());

        // تیک‌ها و پاسخ‌ها واقعاً در دیتابیس هستند: ۶ کار زهرا + ۳ کار امیر
        $this->assertSame(9, TaskCompletion::query()->whereNotNull('completed_at')->count());
        $this->assertSame(4, ChallengeQuestionAnswer::query()->count());
        $this->assertSame(6, $this->participant($challenge, 'زهرا محمدی')->completions()->whereNotNull('completed_at')->count());
        $this->assertSame(3, $this->participant($challenge, 'امیر رضایی')->completions()->whereNotNull('completed_at')->count());

        foreach (['زهرا محمدی', 'امیر رضایی', 'نگار کریمی'] as $name) {
            $this->assertTrue(
                $challenge->participants()->where('name', $name)->exists(),
                'شرکت‌کننده نمونه «'.$name.'» ساخته نشد.'
            );
        }

        // و عددهای پیشرفت از همین رکوردها در می‌آیند
        $zahra = $this->participant($challenge, 'زهرا محمدی');

        $this->assertSame(9, $zahra->completedItems());
        $this->assertSame(17, $zahra->totalItems());
        $this->assertSame(53, $zahra->progressPercent());

        $this->assertSame(4, $this->participant($challenge, 'امیر رضایی')->completedItems());
        $this->assertSame(0, $this->participant($challenge, 'نگار کریمی')->completedItems());

        $this->assertSame(25, $challenge->progressPercent());
    }

    public function test_seeding_twice_does_not_duplicate_the_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Challenge::query()->count());
        $this->assertSame(3, Challenge::query()->firstOrFail()->participants()->count());
        $this->assertSame(14, Challenge::query()->firstOrFail()->tasks()->count());
    }

    public function test_the_demo_challenge_seeder_runs_on_its_own(): void
    {
        $this->seed(DemoChallengeSeeder::class);

        $challenge = Challenge::query()->where('slug', DemoChallengeSeeder::SLUG)->firstOrFail();

        $this->assertSame(14, $challenge->tasks()->count());
        // چالش نمونه خودش شرکت‌کننده‌های نمونه را هم می‌سازد
        $this->assertSame(3, $challenge->participants()->count());
    }

    public function test_the_participant_seeder_builds_answers_for_the_demo_challenge(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش دست‌ساز',
            'slug' => DemoChallengeSeeder::SLUG,
            'is_published' => true,
            'starts_on' => today()->subDays(2),
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'اگر یک روز را از دست بدهم چه می‌شود؟']);

        $created = (new DemoParticipantsSeeder)->seedFor($challenge);

        $this->assertSame(3, $created);
        $this->assertSame(3, $challenge->participants()->count());
        $this->assertGreaterThan(0, ChallengeQuestionAnswer::query()->count());
    }

    public function test_the_admin_seeder_creates_the_super_admin_and_demo_admins(): void
    {
        Admin::query()->delete();

        $this->seed(AdminSeeder::class);

        $this->assertSame(3, Admin::query()->count());
        $this->assertTrue(Admin::query()->where('username', 'admin')->firstOrFail()->isSuper());

        $maryam = Admin::query()->where('username', 'maryam')->firstOrFail();
        $this->assertFalse($maryam->isSuper());
        $this->assertTrue($maryam->isActive());
        $this->assertTrue(Hash::check('maryam12345', $maryam->password));

        // اجرای دوباره چیزی را تکرار نمی‌کند و رمز کسی را عوض نمی‌کند
        $maryam->update(['password' => 'changed-by-owner']);

        $this->seed(AdminSeeder::class);

        $this->assertSame(3, Admin::query()->count());
        $this->assertTrue(Hash::check('changed-by-owner', $maryam->refresh()->password));
    }

    public function test_the_settings_seeder_runs_on_its_own(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->assertSame(config('panel.site_name'), Setting::query()->where('key', 'site_name')->value('value'));
    }

    private function participant(Challenge $challenge, string $name): Participant
    {
        $participant = $challenge->participants()->with(['completions', 'questionAnswers'])->where('name', $name)->firstOrFail();
        $participant->setRelation('challenge', $challenge->load(['tasks', 'questions']));

        return $participant;
    }
}
