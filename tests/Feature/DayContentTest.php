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

        config(['panel.password' => 'secret-panel-pass']);
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
        $this->post(route('panel.login.attempt'), ['password' => 'secret-panel-pass']);
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

        $dayOne = mb_strpos($html, 'روز ۱');
        $questionOne = mb_strpos($html, 'پرسش روز یک');
        $dayTwo = mb_strpos($html, 'روز ۲');
        $questionTwo = mb_strpos($html, 'پرسش روز دو');

        $this->assertNotFalse($dayOne);
        $this->assertNotFalse($questionOne);
        $this->assertNotFalse($dayTwo);
        $this->assertNotFalse($questionTwo);

        // پرسش هر روز بین عنوان همان روز و روز بعدی می‌نشیند
        $this->assertTrue($dayOne < $questionOne, 'پرسش روز اول باید بعد از عنوان روز اول باشد.');
        $this->assertTrue($questionOne < $dayTwo, 'پرسش روز اول باید قبل از عنوان روز دوم باشد.');
        $this->assertTrue($dayTwo < $questionTwo, 'پرسش روز دوم باید بعد از عنوان روز دوم باشد.');
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
}
