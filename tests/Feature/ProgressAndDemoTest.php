<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeQuestionAnswer;
use App\Models\ChallengeTask;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پیشرفت با کار و پرسش با هم حساب می‌شود (نه فقط تیک کارها) و داده‌های نمونه
 * واقعاً داخل دیتابیس ساخته می‌شوند.
 */
class ProgressAndDemoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * چالش یک‌روزه‌ای که امروز روز اولش است: یک کار و دو پرسش.
     *
     * @return array{0: Challenge, 1: Participant, 2: ChallengeTask, 3: ChallengeQuestion, 4: ChallengeQuestion}
     */
    private function makeDay(): array
    {
        $challenge = Challenge::create([
            'title' => 'چالش یک‌روزه',
            'slug' => 'one-day',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ]);

        $task = $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $first = $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش اول']);
        $second = $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش دوم']);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $challenge->load(['tasks', 'questions']);
        $participant->setRelation('challenge', $challenge);

        return [$challenge, $participant, $task, $first, $second];
    }

    public function test_answering_questions_counts_towards_progress(): void
    {
        [$challenge, $participant, $task, $first, $second] = $this->makeDay();

        // یک کار + دو پرسش = سه مورد
        $this->assertSame(3, $participant->totalItems());
        $this->assertSame(0, $participant->completedItems());
        $this->assertSame(0, $participant->progressPercent());

        $participant->toggleTask($task);
        $this->assertSame(1, $participant->completedItems());
        $this->assertSame(33, $participant->progressPercent());

        $this->post(route('participant.questions.answer', [$participant, $first]), ['body' => 'پاسخ اول من']);
        $participant->refreshAnswers();
        $this->assertSame(2, $participant->completedItems());
        $this->assertSame(67, $participant->progressPercent());

        $this->post(route('participant.questions.answer', [$participant, $second]), ['body' => 'پاسخ دوم من']);
        $participant->refreshAnswers();
        $this->assertSame(3, $participant->completedItems());
        $this->assertSame(100, $participant->progressPercent());
    }

    public function test_a_day_is_complete_only_when_its_questions_are_answered(): void
    {
        [$challenge, $participant, $task] = $this->makeDay();

        // کار تیک خورده ولی پرسش‌ها بی‌پاسخ‌اند: روز کامل نیست
        $participant->toggleTask($task);
        $this->assertFalse($participant->dayBreakdown()[1]['complete']);
        $this->assertSame(0, $participant->completedDaysCount());
        $this->assertSame(0, $participant->currentStreak());

        // با پاسخ دادن به هر دو پرسش، روز کامل می‌شود
        $questions = $challenge->questions;

        foreach ($questions as $question) {
            $this->post(route('participant.questions.answer', [$participant, $question]), ['body' => 'پاسخ من']);
        }

        $participant->refreshAnswers();
        $this->assertTrue($participant->dayBreakdown()[1]['complete']);
        $this->assertSame(1, $participant->completedDaysCount());
        $this->assertSame(1, $participant->currentStreak());
    }

    public function test_the_progress_bar_in_the_participant_dashboard_mentions_tasks_and_questions(): void
    {
        [$challenge, $participant, $task, $first] = $this->makeDay();

        $participant->toggleTask($task);
        $this->post(route('participant.questions.answer', [$participant, $first]), ['body' => 'پاسخ من']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('کارها و پرسش‌های من')
            ->assertSee('۲ از ۳ مورد انجام شده است', false)
            ->assertSee('۲ از ۳ مورد')
            ->assertSee('۶۷');
    }

    public function test_panel_stats_and_lists_count_questions_too(): void
    {
        [$challenge, $participant, $task, $first] = $this->makeDay();

        $participant->toggleTask($task);
        $this->post(route('participant.questions.answer', [$participant, $first]), ['body' => 'پاسخ من']);

        $this->loginAsAdmin();

        // فهرست شرکت‌کننده‌ها: «۲ از ۳ مورد»
        $this->get(route('panel.participants.index'))
            ->assertOk()
            ->assertSee('۲ از ۳ مورد');

        // صفحه مدیریت چالش: ستون پیشرفت و جدول شرکت‌کننده‌ها
        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('۲ از ۳ مورد (کار و پرسش)');

        // صفحه وضعیت او: «۱ از ۲» پرسش پاسخ‌داده
        $this->get(route('panel.participants.show', $participant))
            ->assertOk()
            ->assertSee('پرسش پاسخ‌داده')
            ->assertSee('۱ از ۲');
    }

    public function test_the_challenge_progress_percent_includes_questions(): void
    {
        [$challenge, $participant, $task, $first] = $this->makeDay();

        // هیچ کاری انجام نشده
        $this->assertSame(0, $challenge->fresh()->progressPercent());

        // فقط کار → یک مورد از سه مورد
        $participant->toggleTask($task);
        $this->assertSame(33, $challenge->fresh()->progressPercent());

        // پاسخ پرسش هم شمرده می‌شود
        $this->post(route('participant.questions.answer', [$participant, $first]), ['body' => 'پاسخ من']);
        $this->assertSame(67, $challenge->fresh()->progressPercent());
    }

    public function test_panel_day_by_day_view_shows_answers_and_missing_answers(): void
    {
        [$challenge, $participant, $task, $first, $second] = $this->makeDay();

        $this->post(route('participant.questions.answer', [$participant, $first]), ['body' => 'من همیشه صبح‌ها پیاده‌روی می‌کنم.']);

        $this->loginAsAdmin();

        $this->get(route('panel.participants.show', $participant))
            ->assertOk()
            ->assertSee('کارها و پرسش‌های او، روز به روز')
            // کار او با وضعیت انجام‌نشده
            ->assertSee('کار روز اول')
            ->assertSee('انجام نشده')
            // پرسش پاسخ‌داده‌شده با متن پاسخ
            ->assertSee('پرسش اول')
            ->assertSee('من همیشه صبح‌ها پیاده‌روی می‌کنم.')
            // پرسش بی‌پاسخ با برچسب «جواب نداده است»
            ->assertSee('پرسش دوم')
            ->assertSee('جواب نداده است');
    }

    public function test_panel_day_by_day_view_shows_his_answers_without_needing_a_load_of_all_answers(): void
    {
        [$challenge, $participant, $task, $first, $second] = $this->makeDay();

        $this->loginAsAdmin();

        // پرسش‌های بدون پاسخ همه با برچسب «جواب نداده است» نشان داده می‌شوند
        // (یکی هم در توضیح بالای همین بخش آمده است)
        $response = $this->get(route('panel.participants.show', $participant))->assertOk();
        $content = $response->getContent();

        $badge = '<p class="mt-2 rounded-xl bg-danger-soft px-3 py-2 text-xs font-medium text-danger">جواب نداده است</p>';
        $this->assertSame(2, substr_count($content, $badge));

        // کار انجام‌نشده هم برچسب «انجام نشده» دارد
        $this->assertGreaterThan(0, substr_count($content, 'انجام نشده'));
    }

    public function test_demo_seeder_fills_the_database_with_real_data(): void
    {
        $this->seed();

        $challenge = Challenge::query()->firstOrFail();

        $this->assertGreaterThan(0, $challenge->tasks()->count());
        $this->assertGreaterThan(0, $challenge->questions()->count());
        $this->assertSame(3, $challenge->participants()->count());
        $this->assertSame(1, $challenge->tips()->count() >= 1 ? 1 : 0);
        $this->assertSame(1, $challenge->attachments()->count());

        // پاسخ‌های نمونه هم در دیتابیس هستند
        $this->assertGreaterThan(0, ChallengeQuestionAnswer::query()->count());

        // پیشرفت شرکت‌کننده‌ها از روی داده‌های دیتابیس حساب می‌شود
        $participant = $challenge->participants()->with(['completions', 'questionAnswers'])->firstOrFail();
        $participant->setRelation('challenge', $challenge->load(['tasks', 'questions']));

        $this->assertGreaterThan(0, $participant->totalItems());
        $this->assertGreaterThan(0, $participant->completedItems());
        $this->assertGreaterThan(0, $participant->progressPercent());
    }

    public function test_homepage_shows_real_data_from_the_database_not_hardcoded_numbers(): void
    {
        $this->seed();

        $challenge = Challenge::query()->firstOrFail()->load(['tasks', 'questions', 'participants.questionAnswers']);

        $participant = $challenge->participants
            ->sortByDesc(fn (Participant $participant) => $participant->progressPercent())
            ->firstOrFail();
        $participant->setRelation('challenge', $challenge);

        $response = $this->get(route('home'))->assertOk();

        $content = $response->getContent();

        // همان اعداد واقعی دیتابیس در پیش‌نمایش صفحه اصلی دیده می‌شود
        $this->assertStringContainsString(fa_num($participant->completedItems()).' از '.fa_num($participant->totalItems()).' مورد', $content);
        $this->assertStringContainsString(fa_num($participant->progressPercent()).'<span', $content);
        $this->assertStringContainsString(fa_num($participant->currentStreak()).' روز پیوسته', $content);

        // و دیگر عددهای دستی قبلی وجود ندارد
        $this->assertStringNotContainsString('۵ روز پیوسته', $content);
        $this->assertStringNotContainsString('۱۷ کار انجام‌شده', $content);
        $this->assertStringNotContainsString('۸ کار باقی‌مانده', $content);
    }

    public function test_flash_messages_are_shown_once_on_the_admins_page(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        // مثل مرورگر: فرم را می‌فرستیم و ریدایرکت را دنبال می‌کنیم
        $response = $this->followingRedirects()->from(route('panel.admins.index'))->post(route('panel.admins.store'), [
            'name' => 'مریم احمدی',
            'username' => 'maryam',
            'password' => 'maryam-pass',
            'password_confirmation' => 'maryam-pass',
        ])->assertOk();

        // پیام موفقیت فقط یک بار روی صفحه می‌آید
        $this->assertSame(1, substr_count($response->getContent(), 'مدیر «مریم احمدی» ساخته شد'));
    }

    public function test_the_flash_component_is_not_duplicated_in_panel_views(): void
    {
        $views = glob(resource_path('views/panel/*.blade.php')) + glob(resource_path('views/panel/*/*.blade.php'));

        foreach ($views as $view) {
            $markup = file_get_contents($view);

            // صفحه‌هایی که داخل چیدمان پنل می‌آیند، خودشان نباید پیام‌ها را دوباره بیاورند
            if (! str_contains($markup, 'layouts.panel')) {
                continue;
            }

            $this->assertStringNotContainsString(
                '<x-flash',
                $markup,
                'صفحه '.basename($view).' پیام‌ها را دوباره نمایش می‌دهد؛ چیدمان پنل خودش این کار را می‌کند.'
            );
        }
    }

    public function test_validation_errors_are_shown_once_on_the_admins_page(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        // مثل مرورگر: فرم ناقص فرستاده می‌شود و همان صفحه دوباره نمایش داده می‌شود
        $response = $this->followingRedirects()->from(route('panel.admins.index'))->post(route('panel.admins.store'), [
            'name' => '',
            'username' => 'admin',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertOk();

        $content = $response->getContent();

        // فقط یک کادر خلاصه خطاها روی صفحه می‌آید (خطاها کنار خودِ فیلدها هم می‌آیند)
        $this->assertSame(1, substr_count($content, 'bg-danger-soft px-4 py-3 text-sm text-danger'));

        // کادر خطاها فقط یک فهرست دارد (فهرست ناوبری سایدبار جدا است)
        $errorBox = substr($content, (int) strpos($content, 'bg-danger-soft px-4 py-3 text-sm text-danger'), 800);
        $this->assertSame(1, substr_count($errorBox, '<ul class="space-y-1">'));
        $this->assertGreaterThanOrEqual(1, substr_count($content, 'نام مدیر را وارد کنید.'));
    }
}
