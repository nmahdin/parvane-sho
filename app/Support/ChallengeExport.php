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
            'وضعیت روز به روز' => self::daysSheet($challenge, $participants),
            'کارها و پرسش‌ها' => self::itemsSheet($challenge, $participants),
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
            $columns[] = [
                'label' => 'روز '.fa_num($day).' ('.fa_date($challenge->dateForDay($day)).')',
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
        $rows = [[
            'روز',
            'تاریخ',
            'نوع',
            'عنوان',
            'انجام‌شده',
            'انجام داده‌اند',
            'انجام نداده‌اند',
            'پاسخ مدیر',
        ]];

        foreach ($challenge->contentByDay() as $day => $content) {
            $dayNumber = (int) $day;

            foreach ($content['tasks'] as $task) {
                $rows[] = self::taskRow($challenge, $task, $participants);
            }

            foreach ($content['questions'] as $question) {
                $rows[] = self::questionRow($challenge, $question, $participants, $dayNumber);
            }
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

    private static function questionRow(Challenge $challenge, ChallengeQuestion $question, Collection $participants, int $dayNumber): array
    {
        $status = $question->statusForParticipants($participants);
        $day = $question->day_number ?? ($dayNumber ?: null);

        return [
            self::dayLabel($challenge, $day),
            self::dayDate($challenge, $day),
            'پرسش',
            $question->question,
            count($status['answered']).' از '.$status['total'],
            self::names($status['answered']),
            self::names($status['pending']),
            $question->answer ?: '—',
        ];
    }

    private static function dayLabel(Challenge $challenge, ?int $day): string
    {
        if (! $day) {
            return 'بدون روز مشخص';
        }

        return 'روز '.fa_num($day).' از '.fa_num($challenge->totalDays());
    }

    private static function dayDate(Challenge $challenge, ?int $day): string
    {
        return $day ? fa_date($challenge->dateForDay($day)) : '—';
    }
}
