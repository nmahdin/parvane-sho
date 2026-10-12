<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeTask;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    }

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش روزانه',
            'slug' => 'day-content',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ], $attributes));
    }

    private function login(): void
    {
        $this->loginAsAdmin();
    }

    /**
     * تاریخ شمسی روز n ام یک چالش.
     */
    private function jalaliDateOfDay(Challenge $challenge, int $day): string
    {
        return Jalali::inputValue($challenge->dateForDay($day));
    }

    public function test_task_can_be_added_by_choosing_the_day_date(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'task',
            'date' => $this->jalaliDateOfDay($challenge, 3),
            'title' => 'کار روز سوم',
            'description' => 'توضیح کار',
        ])->assertRedirect();

        $task = ChallengeTask::query()->firstOrFail();

        $this->assertSame('کار روز سوم', $task->title);
        $this->assertSame(3, $task->day_number);
    }

    public function test_question_can_be_added_to_a_day_by_its_date(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'date' => $this->jalaliDateOfDay($challenge, 1),
            'question' => 'پرسش روز اول',
            'answer' => 'پاسخ روز اول',
            'is_published' => '1',
        ])->assertRedirect();

        $question = ChallengeQuestion::query()->firstOrFail();

        $this->assertSame(1, $question->day_number);
        $this->assertTrue($question->is_published);

        // پرسش باید داخل کارت همان روز در صفحه عمومی دیده شود
        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('روز ۱')
            ->assertSee('پرسش روز اول')
            ->assertSee('پاسخ روز اول');
    }

    public function test_a_question_without_a_date_lands_in_the_general_group(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'question' => 'پرسش عمومی',
            'answer' => 'پاسخ عمومی',
        ])->assertRedirect();

        $this->assertNull(ChallengeQuestion::query()->firstOrFail()->day_number);

        // در صفحه عمومی، پرسش‌های بدون روز در گروه «کارهای کلی» می‌نشینند
        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('کارهای کلی')
            ->assertSee('پرسش عمومی');
    }

    public function test_invalid_or_missing_input_is_rejected(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        // تاریخ نامعتبر شمسی (ماه ۱۳)
        $this->post(route('panel.days.store', $challenge), [
            'type' => 'task',
            'date' => '۱۴۰۵/۱۳/۴۵',
            'title' => 'کار با تاریخ غلط',
        ])->assertSessionHasErrors('date');

        // کار بدون عنوان
        $this->post(route('panel.days.store', $challenge), [
            'type' => 'task',
            'date' => $this->jalaliDateOfDay($challenge, 1),
        ])->assertSessionHasErrors('title');

        // پرسش بدون متن
        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'date' => $this->jalaliDateOfDay($challenge, 1),
        ])->assertSessionHasErrors('question');

        $this->assertSame(0, ChallengeTask::query()->count());
        $this->assertSame(0, ChallengeQuestion::query()->count());
    }

    public function test_questions_of_locked_days_are_hidden_from_visitors(): void
    {
        $challenge = $this->makeChallenge();

        ChallengeQuestion::create([
            'challenge_id' => $challenge->id,
            'day_number' => 1,
            'question' => 'پرسش امروز',
            'answer' => 'پاسخ امروز',
            'is_published' => true,
        ]);

        ChallengeQuestion::create([
            'challenge_id' => $challenge->id,
            'day_number' => 5,
            'question' => 'پرسش روز پنجم',
            'answer' => 'پاسخ روز پنجم',
            'is_published' => true,
        ]);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('پرسش امروز')
            ->assertDontSee('پرسش روز پنجم');

        // مدیر در پنل همه روزها را می‌بیند
        $this->login();

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('پرسش روز پنجم');
    }

    public function test_question_can_be_moved_to_another_day_by_editing_its_date(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        $question = ChallengeQuestion::create([
            'challenge_id' => $challenge->id,
            'day_number' => 1,
            'question' => 'پرسش جابه‌جاشدنی',
            'is_published' => true,
        ]);

        $this->put(route('panel.questions.update', [$challenge, $question]), [
            'date' => $this->jalaliDateOfDay($challenge, 4),
            'question' => 'پرسش جابه‌جاشدنی',
            'answer' => 'پاسخ تازه',
            'is_published' => '1',
        ])->assertRedirect();

        $this->assertSame(4, $question->fresh()->day_number);

        // با خالی گذاشتن تاریخ، پرسش به گروه «بدون روز مشخص» می‌رود
        $this->put(route('panel.questions.update', [$challenge, $question]), [
            'date' => '',
            'question' => 'پرسش جابه‌جاشدنی',
            'is_published' => '1',
        ])->assertRedirect();

        $this->assertNull($question->fresh()->day_number);
    }

    public function test_question_order_is_kept_inside_each_day(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'day_number' => 2,
            'question' => 'پرسش اول روز دوم',
        ]);

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'day_number' => 2,
            'question' => 'پرسش دوم روز دوم',
        ]);

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'day_number' => 3,
            'question' => 'پرسش اول روز سوم',
        ]);

        $second = ChallengeQuestion::query()->where('question', 'پرسش دوم روز دوم')->firstOrFail();

        // جابه‌جایی فقط روی پرسش‌های همان روز اثر می‌گذارد
        $this->patch(route('panel.questions.move', [$challenge, $second, 'up']))->assertRedirect();

        $order = ChallengeQuestion::query()
            ->where('day_number', 2)
            ->orderBy('sort_order')
            ->pluck('question')
            ->all();

        $this->assertSame(['پرسش دوم روز دوم', 'پرسش اول روز دوم'], $order);
        $this->assertSame(1, ChallengeQuestion::query()->where('day_number', 3)->value('sort_order'));
    }

    public function test_public_page_keeps_questions_inside_their_day_card(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز یک']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز دو']);

        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش روز یک', 'is_published' => true]);
        $challenge->questions()->create(['day_number' => 2, 'question' => 'پرسش روز دو', 'is_published' => true]);

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();

        $dayOne = $this->dayBoxHtml($html, 1);
        $dayTwo = $this->dayBoxHtml($html, 2);

        // پرسش هر روز فقط داخل کارت همان روز می‌نشیند
        $this->assertStringContainsString('پرسش روز یک', $dayOne);
        $this->assertStringContainsString('کار روز یک', $dayOne);
        $this->assertStringNotContainsString('پرسش روز دو', $dayOne, 'پرسش روز دوم نباید داخل کارت روز اول بیفتد.');
        $this->assertStringNotContainsString('کار روز دو', $dayOne);

        $this->assertStringContainsString('پرسش روز دو', $dayTwo);
        $this->assertStringNotContainsString('پرسش روز یک', $dayTwo);

        // و ترتیب داخل کارت: پرسش‌ها قبل از چالش عملی
        $this->assertLessThan(
            strpos($dayOne, 'کار روز یک'),
            strpos($dayOne, 'پرسش روز یک'),
            'داخل کارت روز، اول پرسش‌ها و بعد چالش عملی می‌آید.'
        );
    }

    public function test_question_inside_a_day_is_shown_in_the_participant_dashboard(): void
    {
        $challenge = $this->makeChallenge();

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار امروز']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش امروز من', 'answer' => 'پاسخ امروز من', 'is_published' => true]);
        $challenge->questions()->create(['day_number' => 4, 'question' => 'پرسش روز چهارم', 'is_published' => true]);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('کار امروز')
            ->assertSee('پرسش امروز من')
            ->assertSee('پاسخ امروز من')
            ->assertDontSee('پرسش روز چهارم');
    }

    public function test_panel_day_cards_show_the_date_of_each_day(): void
    {
        $challenge = $this->makeChallenge();
        $this->login();

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز یک']);

        $label = $challenge->dayDateLabel(1);

        $this->assertNotNull($label);

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('کار روز یک')
            ->assertSee($label)
            ->assertSee('data-day-type-form', false)
            ->assertDontSee('id="qa"', false);
    }

    public function test_jalali_parser_rejects_out_of_range_dates(): void
    {
        $this->assertNull(Jalali::parseInput('۱۴۰۵/۱۳/۰۱'));
        $this->assertNull(Jalali::parseInput('۱۴۰۵/۰۰/۱۰'));
        $this->assertNull(Jalali::parseInput('۱۴۰۵/۰۷/۳۲'));
        $this->assertNull(Jalali::parseInput('۱۴۰۵/۱۲/۳۰')); // ۱۴۰۵ کبیسه نیست

        $this->assertSame('2026-10-12', Jalali::parseInput('۱۴۰۵/۰۷/۲۰')?->toDateString());
        $this->assertSame('2026-10-12', Jalali::parseInput('2026-10-12')?->toDateString());
        $this->assertSame('2025-03-20', Jalali::parseInput('۱۴۰۳/۱۲/۳۰')?->toDateString()); // ۱۴۰۳ کبیسه است
    }

    public function test_day_number_is_computed_from_the_challenge_start_date(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => today()->subDays(4)]);

        foreach (range(1, 5) as $day) {
            $challenge->tasks()->create(['day_number' => $day, 'title' => 'کار روز '.$day]);
        }

        $challenge->load('tasks');

        $this->assertSame(1, $challenge->dayNumberForDate(today()->subDays(4)));
        $this->assertSame(5, $challenge->dayNumberForDate(today()));
        $this->assertSame(6, $challenge->dayNumberForDate(today()->addDay()));
        $this->assertSame(1, $challenge->dayNumberForDate(today()->subDays(30))); // قبل از شروع، حداقل روز ۱

        $this->assertSame(today()->subDays(4)->toDateString(), $challenge->dateForDay(1)->toDateString());
        $this->assertSame(5, $challenge->currentDayNumber());
    }

    public function test_questions_are_counted_like_tasks(): void
    {
        $challenge = $this->makeChallenge();

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار ۱']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار ۲']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش ۱', 'is_published' => true]);
        $challenge->questions()->create(['day_number' => 3, 'question' => 'پرسش ۲', 'is_published' => true]);
        $challenge->questions()->create(['day_number' => 3, 'question' => 'پرسش ۳', 'is_published' => true]);

        $challenge->load(['tasks', 'questions']);

        $this->assertSame(2, $challenge->tasksCount());
        $this->assertSame(3, $challenge->questionsCount());
        $this->assertSame(5, $challenge->contentCount());
        $this->assertSame(3, $challenge->questionsCount());

        // روزی که فقط پرسش دارد هم یک روز از چالش است
        $this->assertSame(3, $challenge->daysCount());
        $this->assertSame(3, $challenge->contentDaysCount());

        $this->assertSame(['tasks' => 1, 'questions' => 1, 'total' => 2], $challenge->dayCounts(1));
        $this->assertSame(['tasks' => 0, 'questions' => 2, 'total' => 2], $challenge->dayCounts(3));
    }

    /**
     * تکه HTML باکس یک روز از یک صفحه.
     */
    private function dayBoxHtml(string $html, int $day): string
    {
        $start = strpos($html, 'id="day-'.$day.'"');
        $this->assertNotFalse($start, 'باکس روز '.$day.' ساخته نشده است.');

        $next = strpos($html, 'id="day-'.($day + 1).'"', $start);

        return substr($html, $start, $next === false ? null : $next - $start);
    }

    public function test_locked_days_do_not_show_the_number_of_tasks_questions_or_tips(): void
    {
        $challenge = $this->makeChallenge();

        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار روز دوم']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'کار دوم روز دوم']);
        $challenge->questions()->create(['day_number' => 2, 'question' => 'پرسش اول روز دوم', 'is_published' => true]);
        $challenge->questions()->create(['day_number' => 2, 'question' => 'پرسش دوم روز دوم', 'is_published' => true]);
        $challenge->tips()->create(['day_number' => 2, 'title' => 'نکته روز دوم', 'emoji' => '💡']);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        // روز دوم فردا است؛ پس برای شرکت‌کننده و بازدیدکننده قفل است
        foreach ([route('participant.show', $participant), route('challenges.show', $challenge)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // کارت روز قفل اصلاً ساخته نمی‌شود؛ فقط یک نوار جمع‌وجور با تاریخ‌ها
            $this->assertStringNotContainsString('id="day-2"', $html, 'کارت روز قفل نباید ساخته شود: '.$url);

            $start = strpos($html, 'id="locked-days"');
            $this->assertNotFalse($start, 'نوار روزهای قفل ساخته نشده است: '.$url);
            $strip = substr($html, $start, 2600);

            // نه تعداد کار و پرسش، نه برچسب «در انتظار» و نه نکته
            $this->assertStringNotContainsString('۲ کار', $strip, 'در نوار قفل تعداد کارها نوشته شده است: '.$url);
            $this->assertStringNotContainsString('۲ پرسش', $strip, 'در نوار قفل تعداد پرسش‌ها نوشته شده است: '.$url);
            $this->assertStringNotContainsString('در انتظار', $strip);
            $this->assertStringNotContainsString('نکته', $strip, 'در نوار قفل نکته‌ها نشان داده شده‌اند: '.$url);
            $this->assertStringContainsString('هنوز باز نشده', $strip);
            $this->assertStringContainsString('هنوز آزاد نشده', $strip);

            // و خود محتوای روز قفل هم در صفحه نیست
            $this->assertStringNotContainsString('کار روز دوم', $html);
            $this->assertStringNotContainsString('پرسش اول روز دوم', $html);
            $this->assertStringNotContainsString('نکته روز دوم', $html);
        }

        // مدیر همه‌چیز را می‌بیند
        $this->login();

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('کار روز دوم')
            ->assertSee('پرسش اول روز دوم')
            ->assertSee('نکته روز دوم');
    }

    public function test_tips_come_before_tasks_and_questions_on_the_challenge_page(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش روز اول', 'is_published' => true]);
        $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته روز اول', 'emoji' => '💡']);
        $challenge->tips()->create(['title' => 'نکته کلی چالش', 'emoji' => '📌']);

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();
        $box = $this->dayBoxHtml($html, 1);

        $tips = strpos($box, 'نکته‌های این روز');
        $task = strpos($box, 'کار روز اول');
        $question = strpos($box, 'پرسش روز اول');

        $this->assertNotFalse($tips, 'باکس نکته‌های روز در کارت روز نیست.');
        $this->assertNotFalse($task);
        $this->assertNotFalse($question);

        $this->assertLessThan($task, $tips, 'نکته‌های روز باید بالای کارها بیاید.');
        $this->assertLessThan($question, $tips, 'نکته‌های روز باید بالای پرسش‌ها بیاید.');

        // نکته‌های کلی هم بالای بخش روزها می‌آیند
        $this->assertLessThan(
            strpos($html, 'روزها، کارها و پرسش‌ها'),
            strpos($html, 'نکته کلی چالش'),
            'نکته‌های کلی چالش باید بالای بخش روزها و کارها بیاید.'
        );
    }

    public function test_tips_come_before_tasks_and_questions_in_the_participant_dashboard(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش روز اول', 'is_published' => true]);
        $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته روز اول', 'emoji' => '💡']);
        $challenge->tips()->create(['title' => 'نکته کلی چالش', 'emoji' => '📌']);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();
        $box = $this->dayBoxHtml($html, 1);

        $tips = strpos($box, 'نکته‌های این روز');
        $task = strpos($box, 'کار روز اول');
        $question = strpos($box, 'پرسش روز اول');

        $this->assertNotFalse($tips, 'باکس نکته‌های روز در کارت روز نیست.');
        $this->assertNotFalse($task);
        $this->assertNotFalse($question);

        $this->assertLessThan($task, $tips, 'نکته‌های روز باید بالای کارها بیاید.');
        $this->assertLessThan($question, $tips, 'نکته‌های روز باید بالای پرسش‌ها بیاید.');

        // نکته‌های کلی بالای کارت روزها هستند، نه بعد از آن‌ها
        $this->assertLessThan(
            strpos($html, 'id="day-1"'),
            strpos($html, 'نکته کلی چالش'),
            'نکته‌های کلی چالش باید بالای کارت روزها بیاید.'
        );
    }

    public function test_practical_heading_is_today_for_the_current_day_and_numbered_for_the_rest(): void
    {
        $challenge = $this->makeChallenge();

        $this->assertSame('چالش عملی امروز', $challenge->tasksHeading(1));
        $this->assertSame('چالش عملی روز ۲', $challenge->tasksHeading(2));
        $this->assertSame('چالش عملی', $challenge->tasksHeading(0));
        $this->assertSame('چالش عملی', $challenge->tasksHeading(null));

        // روزهای گذشته «چالش عملی امروز» نمی‌گیرند
        $past = $this->makeChallenge([
            'slug' => 'day-content-past',
            'starts_on' => today()->subDays(4),
        ]);

        $this->assertSame('چالش عملی روز ۱', $past->tasksHeading(1));
        $this->assertSame('چالش عملی امروز', $past->tasksHeading(5));
    }

    public function test_public_challenge_page_puts_questions_first_and_numbers_the_practical_tasks(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار اول روز']);
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار دوم روز']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش روز اول', 'is_published' => true]);

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();
        $box = $this->dayBoxHtml($html, 1);

        // اول پرسش‌ها، بعد چالش عملی
        $this->assertLessThan(
            strpos($box, 'کار اول روز'),
            strpos($box, 'پرسش روز اول'),
            'در صفحه چالش اول باید پرسش‌ها بیایند و بعد چالش عملی.'
        );

        // بالای کارها «چالش عملی امروز» نوشته شده است
        $this->assertStringContainsString('چالش عملی امروز', $box);
        $this->assertLessThan(strpos($box, 'کار اول روز'), strpos($box, 'چالش عملی امروز'));

        // هر کار در صفحه عمومی یک شماره دارد (برای کسانی که با کد وارد نشده‌اند)
        preg_match_all('/ring-gold\/40">\s*([۰-۹]+)\s*</u', $box, $numbers);

        $this->assertSame(['۱', '۲'], $numbers[1], 'کارهای صفحه عمومی باید شماره‌دار باشند.');
        $this->assertStringNotContainsString('✓', $box, 'صفحه عمومی نباید تیک کار داشته باشد.');
    }

    public function test_participant_dashboard_puts_questions_first_and_keeps_the_practical_heading(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش روز اول', 'is_published' => true]);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();
        $box = $this->dayBoxHtml($html, 1);

        $this->assertLessThan(
            strpos($box, 'کار روز اول'),
            strpos($box, 'پرسش روز اول'),
            'در داشبورد شرکت‌کننده هم اول پرسش‌ها و بعد چالش عملی می‌آید.'
        );

        $this->assertStringContainsString('چالش عملی امروز', $box);
        // شرکت‌کننده تیک زدن دارد، نه شماره
        $this->assertStringContainsString('✓', $box);
    }

    public function test_today_gets_a_colored_border_and_the_practical_tasks_stack_under_each_other(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        foreach ([1, 2, 3] as $index) {
            $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار شماره '.$index]);
        }

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();

        // فقط کارت روز جاری حاشیه رنگی دارد و باز است
        $this->assertSame(1, substr_count($html, 'day-card is-today'), 'فقط روز جاری باید حاشیه رنگی بگیرد.');
        $this->assertSame(1, substr_count($html, 'day-card is-today'));

        preg_match('/<details id="day-(\d+)"[^>]*class="day-card is-today"[^>]*>/', $html, $today);
        $this->assertNotEmpty($today, 'کارت روز جاری پیدا نشد.');
        $this->assertSame('1', $today[1]);
        $this->assertStringContainsString('open', $today[0], 'کارت روز جاری باید باز باشد.');

        $box = $this->dayBoxHtml($html, 1);

        // کارهای «چالش عملی» همه زیر هم می‌آیند، نه دو ستونه
        $this->assertStringContainsString('<ul class="mt-2.5 space-y-2">', $box);
        $this->assertStringNotContainsString('sm:grid-cols-2"', $box);

        // دکمه آکاردئون بزرگ و آیکون‌دار است
        $this->assertStringContainsString('class="day-chev"', $box);
        $this->assertStringContainsString('M6 9l6 6 6-6', $box, 'آیکون دکمه آکاردئون در کارت روز نیست.');
    }

    public function test_day_cards_count_tasks_and_questions_together(): void
    {
        $challenge = $this->makeChallenge(['daily_release' => false]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار تنها']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش تنها', 'is_published' => true]);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش دوم', 'is_published' => true]);

        // در صفحه عمومی ترتیب «پرسش‌ها و بعد چالش عملی» است، پس شمارش هم اول پرسش می‌آید
        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('۲ پرسش · ۱ کار', false);

        $this->login();

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('۱ کار · ۲ پرسش', false)
            ->assertSee('کل موارد (کار + پرسش)');
    }

    public function test_content_can_be_added_for_a_past_day(): void
    {
        // چالش ۵ روز پیش شروع شده است؛ روزهای ۱ تا ۴ گذشته‌اند
        $challenge = $this->makeChallenge(['starts_on' => today()->subDays(4)]);
        $this->login();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'task',
            'date' => $this->jalaliDateOfDay($challenge, 2),
            'title' => 'کار روز گذشته',
        ])->assertRedirect();

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'question',
            'date' => $this->jalaliDateOfDay($challenge, 3),
            'question' => 'پرسش روز گذشته',
        ])->assertRedirect();

        $this->assertSame(2, ChallengeTask::query()->firstOrFail()->day_number);
        $this->assertSame(3, ChallengeQuestion::query()->firstOrFail()->day_number);

        // و در فهرست روزهای پنل، همه روزهای گذشته هستندهستند
        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('روزهای چالش')
            ->assertSee('روز '.fa_num(1))
            ->assertSee('روز '.fa_num(5));
    }

    public function test_a_date_before_the_challenge_start_is_rejected(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => today()->subDays(2)]);
        $this->login();

        $beforeStart = Jalali::inputValue($challenge->dateForDay(1)->subDay());

        $this->post(route('panel.days.store', $challenge), [
            'type' => 'task',
            'date' => $beforeStart,
            'title' => 'کار قبل از شروع',
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, ChallengeTask::query()->count());
    }

    public function test_selectable_days_cover_the_challenge_range(): void
    {
        $challenge = $this->makeChallenge([
            'starts_on' => today()->subDays(2),
            'ends_on' => today()->addDays(4),
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز ۱']);
        $challenge->load('tasks');

        // روزهای ۱ تا ۷ (شروع تا پایان) در فهرست انتخاب روزها هستند
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $challenge->selectableDays()->all());
        $this->assertSame(7, $challenge->plannedDaysCount());
    }
}
