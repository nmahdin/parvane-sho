<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

#[Fillable(['challenge_id', 'name', 'contact', 'note', 'token', 'last_seen_at'])]
#[RouteKey('token')]
class Participant extends Model
{
    /**
     * طول کد اختصاصی هر شرکت‌کننده (لینک کوتاه مثل /p/k7m2p9qz).
     */
    public const TOKEN_LENGTH = 8;

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Participant $participant) {
            $participant->token ??= static::generateToken();
        });
    }

    /**
     * Generate an unguessable, easy to type token for public dashboards.
     */
    public static function generateToken(int $length = self::TOKEN_LENGTH): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';

        do {
            $token = '';

            for ($i = 0; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * @return HasMany<TaskCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * Ids of the tasks this participant has completed (cached per request).
     *
     * @return array<int, int>
     */
    public function completedTaskIds(): array
    {
        if ($this->completedIds !== null) {
            return $this->completedIds;
        }

        $ids = $this->relationLoaded('completions')
            ? $this->completions->whereNotNull('completed_at')->pluck('challenge_task_id')
            : $this->completions()->whereNotNull('completed_at')->pluck('challenge_task_id');

        return $this->completedIds = $ids->map(fn ($id) => (int) $id)->all();
    }

    private ?array $completedIds = null;

    public function isTaskDone(ChallengeTask $task): bool
    {
        return in_array((int) $task->id, $this->completedTaskIds(), true);
    }

    /**
     * Mark a task as done or undone. Returns the new state.
     */
    public function toggleTask(ChallengeTask $task): bool
    {
        $completion = $this->completions()->firstOrNew([
            'challenge_task_id' => $task->id,
        ]);

        $isDone = $completion->exists && $completion->completed_at !== null;

        if ($isDone) {
            $completion->delete();
        } else {
            $completion->completed_at = Carbon::now();
            $completion->save();
        }

        $this->completedIds = null;
        $this->unsetRelation('completions');

        return ! $isDone;
    }

    public function completedCount(): int
    {
        return count($this->completedTaskIds());
    }

    public function totalTasks(): int
    {
        if ($this->relationLoaded('challenge') && $this->challenge->relationLoaded('tasks')) {
            return $this->challenge->tasks->count();
        }

        return ChallengeTask::where('challenge_id', $this->challenge_id)->count();
    }

    public function progressPercent(): int
    {
        $total = $this->totalTasks();

        if ($total === 0) {
            return 0;
        }

        return (int) round(min(1, $this->completedCount() / $total) * 100);
    }

    /**
     * Days (day numbers) where every task is completed.
     *
     * @return array<int, int>
     */
    public function completedDays(): array
    {
        $tasks = $this->challenge->tasks->groupBy(fn (ChallengeTask $task) => $task->day_number ?? 0);
        $done = $this->completedTaskIds();

        return $tasks
            ->filter(fn ($dayTasks) => $dayTasks->every(fn (ChallengeTask $task) => in_array((int) $task->id, $done, true)))
            ->keys()
            ->map(fn ($day) => (int) $day)
            ->all();
    }

    /**
     * Consecutive completed days up to the current day of the challenge.
     */
    public function currentStreak(): int
    {
        return $this->streakFrom($this->challenge->currentDayNumber());
    }

    /**
     * Longest run of consecutive completed days.
     */
    public function bestStreak(): int
    {
        $daysCount = $this->challenge->daysCount();
        $best = 0;

        for ($day = 1; $day <= $daysCount; $day++) {
            $best = max($best, $this->streakFrom($day));
        }

        return $best;
    }

    private function streakFrom(?int $day): int
    {
        if ($day === null) {
            return 0;
        }

        $completedDays = $this->completedDays();
        $streak = 0;

        if (! in_array($day, $completedDays, true)) {
            $day--;
        }

        while ($day >= 1 && in_array($day, $completedDays, true)) {
            $streak++;
            $day--;
        }

        return $streak;
    }

    /**
     * زمان انجام هر کار: [task_id => Carbon].
     *
     * @return array<int, Carbon>
     */
    public function completionTimes(): array
    {
        $completions = $this->relationLoaded('completions')
            ? $this->completions
            : $this->completions()->get();

        return $completions
            ->whereNotNull('completed_at')
            ->mapWithKeys(fn (TaskCompletion $completion) => [
                (int) $completion->challenge_task_id => $completion->completed_at,
            ])
            ->all();
    }

    /**
     * وضعیت روزبه‌روز شرکت‌کننده در چالش.
     *
     * @return array<int, array{total: int, done: int, complete: bool, is_today: bool, is_past: bool}>
     */
    public function dayBreakdown(): array
    {
        $times = $this->completionTimes();
        $currentDay = $this->challenge->currentDayNumber();
        $breakdown = [];

        foreach ($this->challenge->tasks->groupBy(fn (ChallengeTask $task) => $task->day_number ?? 0) as $day => $tasks) {
            $done = $tasks->filter(fn (ChallengeTask $task) => isset($times[(int) $task->id]))->count();

            $breakdown[(int) $day] = [
                'total' => $tasks->count(),
                'done' => $done,
                'complete' => $done === $tasks->count(),
                'is_today' => $currentDay !== null && (int) $day === $currentDay,
                'is_past' => $currentDay !== null && (int) $day < $currentDay,
            ];
        }

        return $breakdown;
    }

    /**
     * تعداد روزهایی که به پایان رسیده‌اند اما کامل انجام نشده‌اند.
     */
    public function missedDaysCount(): int
    {
        $missed = 0;

        foreach ($this->dayBreakdown() as $day => $row) {
            if ($day > 0 && $row['is_past'] && ! $row['complete']) {
                $missed++;
            }
        }

        return $missed;
    }

    public function completedDaysCount(): int
    {
        return count(array_filter($this->dayBreakdown(), fn (array $row) => $row['complete']));
    }

    /**
     * تعداد کارهای انجام‌شده در هر روز از آخرین :days روز: [Y-m-d => count].
     *
     * @return array<string, int>
     */
    public function activityByDay(int $days = 14): array
    {
        $activity = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $activity[Carbon::today()->subDays($i)->toDateString()] = 0;
        }

        foreach ($this->completionTimes() as $time) {
            $key = $time->toDateString();

            if (array_key_exists($key, $activity)) {
                $activity[$key]++;
            }
        }

        return $activity;
    }

    public function lastActivityAt(): ?Carbon
    {
        $times = $this->completionTimes();

        return $times === [] ? null : Carbon::parse(max($times));
    }

    public function firstActivityAt(): ?Carbon
    {
        $times = $this->completionTimes();

        return $times === [] ? null : Carbon::parse(min($times));
    }

    /**
     * وضعیت کلی شرکت‌کننده برای نگاه مدیر.
     *
     * @return array{label: string, classes: string, tone: string}
     */
    public function activityStatus(): array
    {
        $total = $this->totalTasks();
        $done = $this->completedCount();

        if ($total === 0) {
            return ['label' => 'بدون کار', 'classes' => 'bg-slate-50 text-slate-600 ring-slate-200', 'tone' => 'slate'];
        }

        if ($done === 0) {
            return ['label' => 'شروع نکرده', 'classes' => 'bg-slate-50 text-slate-600 ring-slate-200', 'tone' => 'slate'];
        }

        $currentDay = $this->challenge->currentDayNumber();
        $breakdown = $this->dayBreakdown();

        if ($currentDay !== null && ($breakdown[$currentDay]['complete'] ?? false)) {
            return ['label' => 'امروز را کامل انجام داد', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'tone' => 'emerald'];
        }

        $last = $this->lastActivityAt();

        if ($last && $last->greaterThanOrEqualTo(Carbon::today()->subDays(2))) {
            return ['label' => 'فعال', 'classes' => 'bg-sky-50 text-sky-700 ring-sky-200', 'tone' => 'sky'];
        }

        return ['label' => 'بی‌فعال', 'classes' => 'bg-amber-50 text-amber-700 ring-amber-200', 'tone' => 'amber'];
    }

    /**
     * آخرین تیک‌های زده‌شده برای نمایش در صفحه وضعیت.
     *
     * @return Collection<int, TaskCompletion>
     */
    public function recentCompletions(int $limit = 10)
    {
        return $this->completions()
            ->with('task')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit($limit)
            ->get();
    }

    public function markSeen(): void
    {
        $this->forceFill(['last_seen_at' => Carbon::now()])->saveQuietly();
    }

    public function dashboardUrl(): string
    {
        return route('participant.show', $this);
    }
}
