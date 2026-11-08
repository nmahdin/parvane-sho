<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeQuestionAnswer;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionAnswerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    }

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش پاسخ‌ها',
            'slug' => 'answers',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ], $attributes));
    }

    /**
     * @return array{0: Challenge, 1: Participant, 2: ChallengeQuestion}
     */
    private function makeScenario(): array
    {
        $challenge = $this->makeChallenge();

        $question = $challenge->questions()->create([
            'day_number' => 1,
            'question' => 'امروز بابت چه چیزی شکرگزار بودی؟',
            'answer' => 'یک دقیقه فکر کن و بنویس.',
            'is_published' => true,
        ]);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        return [$challenge, $participant, $question];
    }

    public function test_participant_can_answer_a_question(): void
    {
        [$challenge, $participant, $question] = $this->makeScenario();

        $this->post(route('participant.questions.answer', [$participant, $question]), [
            'body' => 'بابت سلامتی و خانواده‌ام.',
        ])->assertRedirect();

        $answer = ChallengeQuestionAnswer::query()->firstOrFail();

        $this->assertSame($participant->id, $answer->participant_id);
        $this->assertSame($question->id, $answer->challenge_question_id);
        $this->assertSame('بابت سلامتی و خانواده‌ام.', $answer->body);

        // پاسخ در داشبورد خودش دیده می‌شود
        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertSee('بابت سلامتی و خانواده‌ام.')
            ->assertSee('پاسخ دادی');
    }

    public function test_answer_can_be_edited_and_removed(): void
    {
        [$challenge, $participant, $question] = $this->makeScenario();

        $this->post(route('participant.questions.answer', [$participant, $question]), ['body' => 'پاسخ اول']);
        $this->post(route('participant.questions.answer', [$participant, $question]), ['body' => 'پاسخ ویرایش‌شده']);

        $this->assertSame(1, ChallengeQuestionAnswer::query()->count());
        $this->assertSame('پاسخ ویرایش‌شده', ChallengeQuestionAnswer::query()->firstOrFail()->body);

        // پاسخ خالی یعنی برداشتن پاسخ
        $this->post(route('participant.questions.answer', [$participant, $question]), ['body' => ''])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, ChallengeQuestionAnswer::query()->count());
    }

    public function test_locked_questions_cannot_be_answered(): void
    {
        $challenge = $this->makeChallenge();

        $locked = $challenge->questions()->create([
            'day_number' => 4,
            'question' => 'پرسش روز قفل‌شده',
            'is_published' => true,
        ]);

        $participant = $challenge->participants()->create(['name' => 'امیر رضایی']);

        $this->post(route('participant.questions.answer', [$participant, $locked]), ['body' => 'زودتر نوشتم'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, ChallengeQuestionAnswer::query()->count());

        // و پرسش قفل‌شده در داشبورد او دیده نمی‌شود
        $this->get(route('participant.show', $participant))
            ->assertOk()
            ->assertDontSee('پرسش روز قفل‌شده');
    }

    public function test_question_of_another_challenge_or_unpublished_cannot_be_answered(): void
    {
        [, $participant, $question] = $this->makeScenario();

        $otherChallenge = $this->makeChallenge(['title' => 'چالش دیگر', 'slug' => 'other']);

        $foreign = $otherChallenge->questions()->create([
            'day_number' => 1,
            'question' => 'پرسش چالش دیگر',
            'is_published' => true,
        ]);

        $hidden = $question->challenge->questions()->create([
            'day_number' => 1,
            'question' => 'پرسش پنهان',
            'is_published' => false,
        ]);

        $this->post(route('participant.questions.answer', [$participant, $foreign]), ['body' => 'جواب'])
            ->assertNotFound();

        $this->post(route('participant.questions.answer', [$participant, $hidden]), ['body' => 'جواب'])
            ->assertNotFound();

        $this->assertSame(0, ChallengeQuestionAnswer::query()->count());
    }

    public function test_admin_sees_the_answers_in_the_panel(): void
    {
        [$challenge, $participant, $question] = $this->makeScenario();

        $this->post(route('participant.questions.answer', [$participant, $question]), [
            'body' => 'پاسخ زهرا برای مدیر',
        ]);

        $this->loginAsAdmin();

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('پاسخ زهرا برای مدیر')
            ->assertSee('پاسخ‌های شرکت‌کننده‌ها')
            ->assertSee('زهرا محمدی');

        // صفحه وضعیت او: کارها و پرسش‌ها روز به روز، همراه با پاسخش
        $this->get(route('panel.participants.show', $participant))
            ->assertOk()
            ->assertSee('کارها و پرسش‌های او، روز به روز')
            ->assertSee('پاسخ زهرا برای مدیر')
            ->assertSee('امروز بابت چه چیزی شکرگزار بودی؟');
    }

    public function test_questions_are_public_by_default_and_the_public_page_has_no_answer_box(): void
    {
        [$challenge, $participant, $question] = $this->makeScenario();

        $this->assertTrue($question->fresh()->is_published);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('امروز بابت چه چیزی شکرگزار بودی؟')
            ->assertDontSee('پاسخ تو به این پرسش');

        // پاسخ شرکت‌کننده هم در صفحه عمومی نمایش داده نمی‌شود
        $this->post(route('participant.questions.answer', [$participant, $question]), ['body' => 'متن خصوصی پاسخ']);

        $this->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertDontSee('متن خصوصی پاسخ');
    }
}
