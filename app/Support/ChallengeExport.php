<?php

namespace App\Support;

use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeTask;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * خروجی اکسل اطلاعات یک چالش: خلاصه، وضعیت شرکت‌کننده‌ها،
 * وضعیت روز به روز و فهرست کارها و پرسش‌ها با نام انجام‌دهندگان.
 */
class ChallengeExport
{
    /**
     * نام فایل دانلودی (انگلیسی و بدون فاصله تا همه‌جا درست باز شود).
     */
    public static function filename(Challenge $challenge): string
    {
        return 'challenge-'.$challenge->slug.'-'.now()->format('Y-m-d').'.xlsx';
    }

    /**
     * محتوای باینری فایل اکسل.
     */
    public static function content(Challenge $challenge): string
    {
        $writer = new XlsxWriter;

        foreach (self::sheets($challenge) as $name => $rows) {
            $writer->addSheet($name, $rows);
        }

        return $writer->toString();
    }

    /**
     * ساخت فایل اکسل روی دیسک و برگرداندن مسیر موقت آن.
     */
    public static function build(Challenge $challenge): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('challenge-export-', true).'.xlsx';

        if (@file_put_contents($path, self::content($challenge)) === false) {
            throw new \RuntimeException('ساخت فایل اکسل ناموفق بود.');
        }

        return $path;
    }

    /**
     * دانلود فایل اکسل چالش.
     */
    public static function download(Challenge $challenge): BinaryFileResponse
    {
        return response()
            ->download(self::build($challenge), self::filename($challenge), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * برگه‌های خروجی: نام برگه => سطرها.
     *
     * @return array<string, list<list<mixed>>>
     */
    public static function sheets(Challenge $challenge): array
    {
        $challenge->loadMissing([
            'tasks',
            'days',
            'tips',
            'questions.answers',
            'participants.completions',
            'participants.questionAnswers',
        ]);

        $participants = $challenge->participants
            ->each(fn (Participant $participant) => $participant->setRelation('challenge', $challenge))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return [
            'خلاصه چالش' => self::summarySheet($challenge, $participants),
            'شرکت‌کننده‌ها' => self::participantsSheet($challenge, $participants),
            'کارها و پاسخ‌های هر نفر' => self::perParticipantSheet($challenge, $participants),
            'وضعیت روز به روز' => self::daysSheet($challenge, $participants),
            'کارها و پرسش‌ها' => self::itemsSheet($challenge, $participants),
            'نکته‌ها' => self::tipsSheet($challenge),
        ];
    }

    /**
     * @param  Collection<int, Participant>  $participants
     * @return list<list<mixed>>
     */
    private static function summarySheet(Challenge $challenge, Collection $participants): array
    {
        $average = $participants->isEmpty()
            ? 0
            : (int) round($participants->avg(fn (Participant $participant) => $participant->progressPercent()));

        return [
            ['ویژگی', 'مقدار'],
            ['نام چالش', $challenge->title],
            ['توضیح کوتاه', $challenge->tagline ?: '—'],
            ['نشانی صفحه چالش', $challenge->publicUrl()],
            ['وضعیت نمایش', $challenge->statusLabel()],
            ['بازه تاریخ', $challenge->dateRangeLabel()],
            ['تعداد روزها', $challenge->totalDays()],
            ['تعداد کارها', $challenge->tasks->count()],
            ['تعداد پرسش‌ها', $challenge->questions->count()],
            ['تعداد شرکت‌کننده‌ها', $participants->count()],
            ['تعداد نکته‌ها (کلی + هر روز)', $challenge->tips->count()],
            ['میانگین پیشرفت شرکت‌کننده‌ها (٪)', $average],
            ['پیشرفت کلی چالش (٪)', $challenge->progressPercent()],
            ['تاریخ خروجی', Jalali::formatDateTime(now())],
        ];
    }

    /**
     * @param  Collection<int, Participant>  $participants
     * @return list<list<mixed>>
     */
    private static function participantsSheet(Challenge $challenge, Collection $participants): array
    {
        $rows = [[
            'نام',
            'کد اختصاصی',
            'راه ارتباطی',
            'کارهای انجام‌شده',
            'کل کارها',
            'پرسش‌های پاسخ‌داده‌شده',
            'کل پرسش‌ها',
            'موارد انجام‌شده',
            'کل موارد',
            'پیشرفت (٪)',
            'تعهدنامه',
            'روزهای کامل',
            'روزهای از دست‌رفته',
            'زنجیره فعلی',
            'بهترین زنجیره',
            'آخرین فعالیت',
            'آخرین بازدید',
        ]];

        foreach ($participants as $participant) {
            $rows[] = [
                $participant->name,
                $participant->token,
                $participant->contact ?: '—',
                $participant->completedCount(),
                $participant->totalTasks(),
                $participant->answeredQuestionsCount(),
                $participant->questionsCount(),
                $participant->completedItems(),
                $participant->totalItems(),
                $participant->progressPercent(),
                ! $challenge->pledgeEnabled()
                    ? 'غیرفعال'
                    : ($participant->hasPledged() ? 'ثبت شده ('.fa_datetime($participant->pledged_at).')' : 'ثبت نشده'),
                $participant->completedDaysCount(),
                $participant->missedDaysCount(),
                $participant->currentStreak(),
                $participant->bestStreak(),
                $participant->lastActivityAt() ? fa_datetime($participant->lastActivityAt()) : 'هنوز چیزی انجام نداده',
                $participant->last_seen_at ? fa_datetime($participant->last_seen_at) : 'هنوز باز نکرده',
            ];
        }

        if ($participants->isEmpty()) {
            $rows[] = ['هنوز کسی در این چالش نیست.'];
        }

        return $rows;
    }

    /**
     * کارها و پاسخ‌های هر نفر: برای هر شرکت‌کننده، تک‌تک کارها و پرسش‌های چالش
     * با وضعیت انجام‌شده/انجام‌نشده، زمان انجام و متن پاسخ خودش به پرسش‌ها.
     *
     * @param  Collection<int, Participant>  $participants
     * @return list<list<mixed>>
     */
    private static function perParticipantSheet(Challenge $challenge, Collection $participants): array
    {
        $rows = [[
            'نام شرکت‌کننده',
            'کار یا پرسش',
            'روز',
            'تاریخ روز',
            'عنوان یا متن',
            'وضعیت',
            'زمان انجام یا پاسخ',
            'پاسخ خودش',
        ]];

        if ($participants->isEmpty()) {
            $rows[] = ['هنوز کسی در این چالش نیست.', '—', '—', '—', '—', '—', '—', '—'];

            return $rows;
        }

        $items = self::dayItems($challenge);

        foreach ($participants as $participant) {
            if ($items === []) {
                $rows[] = [$participant->name, '—', '—', '—', 'هنوز کاری یا پرسشی ثبت نشده است.', '—', '—', '—'];

                continue;
            }

            $completions = $participant->completions->keyBy('challenge_task_id');
            $answers = $participant->questionAnswers->keyBy('challenge_question_id');

            foreach ($items as $item) {
                $day = $item['day'] ? (int) $item['day'] : null;

                if ($item['type'] === 'task') {
                    $completion = $completions->get($item['model']->getKey());
                    $doneAt = $completion?->completed_at;

                    $rows[] = [
                        $participant->name,
                        'کار',
                        self::dayLabel($challenge, $day),
                        self::dayDate($challenge, $day),
                        $item['model']->title,
                        $doneAt ? '✓ انجام داده' : 'انجام نشده',
                        $doneAt ? fa_datetime($doneAt) : '—',
                        '—',
                    ];

                    continue;
                }

                $answer = $answers->get($item['model']->getKey());
                $body = $answer ? trim((string) $answer->body) : '';

                $rows[] = [
                    $participant->name,
                    'پرسش',
                    self::dayLabel($challenge, $day),
                    self::dayDate($challenge, $day),
                    $item['model']->question,
                    $body !== '' ? '✓ پاسخ داده' : 'جواب نداده است',
                    $body !== '' ? fa_datetime($answer->updated_at) : '—',
                    $body !== '' ? $body : '—',
                ];
            }
        }

        return $rows;
    }

    /**
     * نکته‌های چالش: کلی و مخصوص هر روز.
     *
     * @return list<list<mixed>>
     */
    private static function tipsSheet(Challenge $challenge): array
    {
        $rows = [['روز', 'تاریخ روز', 'نماد', 'عنوان', 'متن']];

        foreach ($challenge->tips->sortBy('sort_order') as $tip) {
            $day = (int) ($tip->day_number ?? 0);

            $rows[] = [
                $day > 0 ? 'روز '.fa_num($day) : 'بدون روز مشخص (کلی)',
                $day > 0 ? fa_date($challenge->dateForDay($day)) : '—',
                $tip->emoji,
                $tip->title,
                $tip->body ?: '—',
            ];
        }

        if ($challenge->tips->isEmpty()) {
            $rows[] = ['—', '—', '—', 'هنوز نکته‌ای ثبت نشده است.', '—'];
        }

        return $rows;
    }

    /**
     * وضعیت هر شرکت‌کننده در هر روز: «X از Y».
     *
     * @param  Collection<int, Participant>  $participants
     * @return list<list<mixed>>
     */
    private static function daysSheet(Challenge $challenge, Collection $participants): array
    {
        // ستون «بدون روز مشخص» فقط وقتی چنین محتوایی وجود دارد
        $hasGeneralDay = $challenge->contentByDay()->has(0);

        $columns = [];

        if ($hasGeneralDay) {
            $columns[] = ['label' => 'بدون روز مشخص', 'day' => 0];
        }

        foreach (range(1, max(1, $challenge->totalDays())) as $day) {
            $title = $challenge->dayTitle($day);

            $columns[] = [
                'label' => 'روز '.fa_num($day).' ('.fa_date($challenge->dateForDay($day)).')'.($title ? ' — '.$title : ''),
                'day' => $day,
            ];
        }

        $header = ['نام شرکت‌کننده'];

        foreach ($columns as $column) {
            $header[] = $column['label'];
        }

        $header[] = 'مجموع';

        $rows = [$header];

        foreach ($participants as $participant) {
            $breakdown = $participant->dayBreakdown();

            $row = [$participant->name];
            $done = 0;
            $total = 0;

            foreach ($columns as $column) {
                $cell = $breakdown[(int) $column['day']] ?? null;

                if ($cell === null) {
                    $row[] = '—';

                    continue;
                }

                $done += $cell['done'];
                $total += $cell['total'];

                $row[] = fa_num($cell['done']).' از '.fa_num($cell['total']).($cell['complete'] ? ' (کامل)' : '');
            }

            $row[] = fa_num($done).' از '.fa_num($total).' · '.fa_num($participant->progressPercent()).'٪';

            $rows[] = $row;
        }

        if ($participants->isEmpty()) {
            $rows[] = ['هنوز کسی در این چالش نیست.'];
        }

        return $rows;
    }

    /**
     * فهرست کارها و پرسش‌ها همراه با کسانی که انجام داده‌اند و کسانی که نداده‌اند.
     *
     * @param  Collection<int, Participant>  $participants
     * @return list<list<mixed>>
     */
    private static function itemsSheet(Challenge $challenge, Collection $participants): array
    {
        $answersByQuestion = self::answersByQuestion($participants);

        $rows = [[
            'روز',
            'تاریخ',
            'نوع',
            'عنوان',
            'انجام‌شده',
            'انجام داده‌اند',
            'انجام نداده‌اند',
            'پاسخ شرکت‌کننده‌ها',
            'پاسخ مدیر',
        ]];

        foreach (self::dayItems($challenge) as $item) {
            $rows[] = $item['type'] === 'task'
                ? self::taskRow($challenge, $item['model'], $participants)
                : self::questionRow($challenge, $item['model'], $participants, (int) $item['day'], $answersByQuestion);
        }

        if (count($rows) === 1) {
            $rows[] = ['هنوز کاری یا پرسشی ثبت نشده است.'];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Participant>  $participants
     * @return list<mixed>
     */
    private static function taskRow(Challenge $challenge, ChallengeTask $task, Collection $participants): array
    {
        $status = $task->statusForParticipants($participants);

        return [
            self::dayLabel($challenge, $task->day_number),
            self::dayDate($challenge, $task->day_number),
            'کار',
            $task->title,
            count($status['done']).' از '.$status['total'],
            self::names($status['done']),
            self::names($status['pending']),
            '—',
        ];
    }

    /**
     * نام کسانی که در یک فهرست آمده‌اند؛ اگر کسی نبود خط تیره.
     *
     * @param  list<Participant|array{participant: Participant}>  $rows
     */
    private static function names(array $rows): string
    {
        if ($rows === []) {
            return '—';
        }

        $names = array_map(
            fn (Participant|array $row) => $row instanceof Participant ? $row->name : $row['participant']->name,
            $rows
        );

        return implode('، ', $names);
    }

    /**
     * @param  Collection<int, list<string>>  $answersByQuestion
     */
    private static function questionRow(
        Challenge $challenge,
        ChallengeQuestion $question,
        Collection $participants,
        int $dayNumber,
        Collection $answersByQuestion,
    ): array {
        $status = $question->statusForParticipants($participants);
        $day = $question->day_number ?? ($dayNumber ?: null);
        $answers = $answersByQuestion->get((int) $question->getKey(), []);

        return [
            self::dayLabel($challenge, $day),
            self::dayDate($challenge, $day),
            'پرسش',
            $question->question,
            count($status['answered']).' از '.$status['total'],
            self::names($status['answered']),
            self::names($status['pending']),
            $answers === [] ? '—' : implode(' | ', $answers),
            $question->answer ?: '—',
        ];
    }

    /**
     * پاسخ شرکت‌کننده‌ها به هر پرسش: شناسه پرسش => فهرست «نام: متن پاسخ».
     *
     * @param  Collection<int, Participant>  $participants
     * @return Collection<int, list<string>>
     */
    private static function answersByQuestion(Collection $participants): Collection
    {
        $map = [];

        foreach ($participants as $participant) {
            foreach ($participant->questionAnswers as $answer) {
                $body = trim((string) $answer->body);

                if ($body === '') {
                    continue;
                }

                $map[(int) $answer->challenge_question_id][] = $participant->name.': '.$body;
            }
        }

        return collect($map);
    }

    /**
     * همه کارها و پرسش‌های چالش به ترتیب روز (اول موارد بدون روز مشخص).
     *
     * @return list<array{day: ?int, type: string, model: ChallengeTask|ChallengeQuestion}>
     */
    private static function dayItems(Challenge $challenge): array
    {
        $items = [];

        foreach ($challenge->contentByDay() as $day => $content) {
            $dayNumber = (int) $day;

            foreach ($content['tasks'] as $task) {
                $items[] = ['day' => $dayNumber ?: null, 'type' => 'task', 'model' => $task];
            }

            foreach ($content['questions'] as $question) {
                $items[] = [
                    'day' => $question->day_number ?? ($dayNumber ?: null),
                    'type' => 'question',
                    'model' => $question,
                ];
            }
        }

        return $items;
    }

    private static function dayLabel(Challenge $challenge, ?int $day): string
    {
        if (! $day) {
            return 'بدون روز مشخص';
        }

        $label = 'روز '.fa_num($day).' از '.fa_num($challenge->totalDays());
        $title = $challenge->dayTitle($day);

        return $title ? $label.' — '.$title : $label;
    }

    private static function dayDate(Challenge $challenge, ?int $day): string
    {
        return $day ? fa_date($challenge->dateForDay($day)) : '—';
    }
}
