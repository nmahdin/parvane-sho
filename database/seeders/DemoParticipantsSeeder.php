<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeTask;
use App\Models\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * سه شرکت‌کننده نمونه با وضعیت‌های متفاوت: یکی تقریباً کامل، یکی در میانه راه
 * و یکی هنوز شروع‌نکرده. پاسخ پرسش‌ها هم اینجا ساخته می‌شود تا هم پیشرفت
 * واقعی باشد و هم مدیر پاسخ‌ها و «جواب نداده»ها را ببیند.
 *
 * مستقل هم اجرا می‌شود: php artisan db:seed --class=DemoParticipantsSeeder
 */
class DemoParticipantsSeeder extends Seeder
{
    public function run(): void
    {
        $challenge = Challenge::query()->where('slug', DemoChallengeSeeder::SLUG)->first();

        if (! $challenge) {
            $this->say('چالش نمونه پیدا نشد؛ اول DemoChallengeSeeder را اجرا کن.');

            return;
        }

        if ($challenge->participants()->exists()) {
            $this->say('شرکت‌کننده‌های نمونه از قبل وجود دارند.');

            return;
        }

        $created = $this->seedFor($challenge);

        $this->say('سه شرکت‌کننده نمونه با کارهای انجام‌شده و پاسخ‌ها ساخته شد.');
    }

    /**
     * ساخت شرکت‌کننده‌های نمونه برای یک چالش؛ تعداد ساخته‌شده را برمی‌گرداند.
     */
    public function seedFor(Challenge $challenge): int
    {
        /** @var Collection<int, ChallengeTask> $tasks */
        $tasks = $challenge->tasks()->orderBy('sort_order')->get();

        $questions = $challenge->questions()->orderBy('sort_order')->get();

        // [نام, تعداد کارهای انجام‌شده, شماره پرسش‌هایی که پاسخ داده‌اند]
        $people = [
            ['name' => 'زهرا محمدی', 'tasks' => 6, 'answers' => [0, 1, 2]],
            ['name' => 'امیر رضایی', 'tasks' => 3, 'answers' => [0]],
            ['name' => 'نگار کریمی', 'tasks' => 0, 'answers' => []],
        ];

        $created = 0;

        foreach ($people as $index => $person) {
            $participant = $challenge->participants()->create([
                'name' => $person['name'],
                'contact' => $index === 0 ? '@zahra' : null,
                'last_seen_at' => Carbon::now()->subHours($index * 5),
            ]);

            $this->completeTasks($challenge, $participant, $tasks, $person['tasks']);
            $this->answerQuestions($participant, $questions, $person['answers']);

            $created++;
        }

        return $created;
    }

    /**
     * چند کار از روزهای باز را برای این شرکت‌کننده تیک می‌زند.
     *
     * @param  Collection<int, ChallengeTask>  $tasks
     */
    private function completeTasks(Challenge $challenge, Participant $participant, Collection $tasks, int $count): void
    {
        $unlocked = $tasks
            ->filter(fn ($task) => $challenge->isDayUnlocked($task->day_number))
            ->values()
            ->take($count);

        foreach ($unlocked as $offset => $task) {
            $participant->completions()->create([
                'challenge_task_id' => $task->id,
                'completed_at' => Carbon::today()
                    ->subDays(max(0, 2 - intdiv($offset, 2)))
                    ->setTime(9 + ($offset % 6), 20),
            ]);
        }
    }

    /**
     * پاسخ‌های نمونه به پرسش‌ها (با شماره اندیس پرسش‌ها).
     *
     * @param  Collection<int, ChallengeQuestion>  $questions
     * @param  list<int>  $indexes
     */
    private function answerQuestions(Participant $participant, Collection $questions, array $indexes): void
    {
        foreach ($indexes as $index) {
            $question = $questions->get($index);

            if (! $question) {
                continue;
            }

            $participant->questionAnswers()->create([
                'challenge_question_id' => $question->id,
                'body' => $this->demoAnswer($participant->name, $question->question),
            ]);
        }
    }

    /**
     * متن پاسخ نمونه برای هر پرسش.
     */
    private function demoAnswer(string $name, string $question): string
    {
        return match (true) {
            str_contains($question, 'یک روز را از دست بدهم') => 'برای من هم پیش آمده؛ دیدم چیزی خراب نمی‌شود و فقط زنجیره‌ام صفر شد، برای همین این بار سعی می‌کنم همان روز را جبران کنم.',
            str_contains($question, 'همان روز انجام') => 'من عصرها بعد از کار انجام می‌دهم؛ غروب سختی دارم که این کارها به بخش ثابت روزم تبدیل شده.',
            default => $name.' هستم و این چالش برایم تمرین خوبی است تا منظم‌تر بمانم.',
        };
    }

    private function say(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
}
