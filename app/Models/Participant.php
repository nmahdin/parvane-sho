<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
     * پاسخ‌های این شرکت‌کننده به پرسش‌های چالش.
     *
     * @return HasMany<ChallengeQuestionAnswer, $this>
     */
    public function questionAnswers(): HasMany
    {
        return $this->hasMany(ChallengeQuestionAnswer::class);
    }

    /**
     * در پنل، مدیر چالش فقط شرکت‌کننده‌های چالش‌های خودش را می‌بیند.
     *
     * @param  Builder<Participant>  $query
     * @return Builder<Participant>
     */
    public function scopeVisibleToAdmin(Builder $query, ?Admin $admin): Builder
    {
        if (! $admin || $admin->isSuper()) {
            return $query;
        }

        return $query->whereHas('challenge', fn (Builder $challenge) => $challenge->where('admin_id', $admin->getKey()));
    }

    /**
     * مدیری که چالش این شرکت‌کننده به او تعلق دارد.
     */
    public function admin(): ?Admin
    {
        return $this->challenge?->admin;
    }

    /**
     * در پنل، هر مدیر فقط شرکت‌کننده‌های چالش‌های خودش را می‌بیند.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $query = static::query();

        if (request()->routeIs('panel.*')) {
            $admin = Auth::guard('admin')->user();

            if ($admin && ! $admin->isSuper()) {
                $query->whereHas('challenge', fn ($challenge) => $challenge->where('admin_id', $admin->getKey()));
            }
        }

        return $query->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
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

    /**
     * کش پاسخ‌ها را پاک می‌کند (بعد از ثبت/برداشتن پاسخ).
     */
    public function refreshAnswers(): void
    {
        $this->answers = null;
        $this->unsetRelation('questionAnswers');
    }

    public function totalTasks(): int
    {
        if ($this->relationLoaded('challenge') && $this->challenge->relationLoaded('tasks')) {
            return $this->challenge->tasks->count();
        }

        return ChallengeTask::where('challenge_id', $this->challenge_id)->count();
    }

    /**
     * پرسش‌های منتشرشده چالش (همان‌هایی که شرکت‌کننده می‌بیند و باید پاسخ بدهد).
     *
     * @return Collection<int, ChallengeQuestion>
     */
    public function challengeQuestions(): Collection
    {
        if ($this->relationLoaded('challenge') && $this->challenge->relationLoaded('questions')) {
            return $this->challenge->questions->where('is_published', true)->values();
        }

        return ChallengeQuestion::query()
            ->where('challenge_id', $this->challenge_id)
            ->where('is_published', true)
            ->get();
    }

    public function questionsCount(): int
    {
        return $this->challengeQuestions()->count();
    }

    /**
     * پاسخ‌های این شرکت‌کننده، کلیدشان شناسه پرسش است.
     *
     * @return array<int, ChallengeQuestionAnswer>
     */
    public function answersByQuestionId(): array
    {
        if ($this->answers !== null) {
            return $this->answers;
        }

        $answers = $this->relationLoaded('questionAnswers')
            ? $this->questionAnswers
            : $this->questionAnswers()->get();

        return $this->answers = $answers
            ->mapWithKeys(fn (ChallengeQuestionAnswer $answer) => [(int) $answer->challenge_question_id => $answer])
            ->all();
    }

    private ?array $answers = null;

    public function answerFor(?ChallengeQuestion $question): ?ChallengeQuestionAnswer
    {
        return $question ? ($this->answersByQuestionId()[(int) $question->id] ?? null) : null;
    }

    public function isQuestionAnswered(?ChallengeQuestion $question): bool
    {
        return $this->answerFor($question) !== null;
    }

    /**
     * تعداد پرسش‌هایی که پاسخ داده است (از بین پرسش‌های منتشرشده چالش).
     */
    public function answeredQuestionsCount(): int
    {
        $publishedIds = $this->challengeQuestions()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return count(array_intersect(array_keys($this->answersByQuestionId()), $publishedIds));
    }

    /**
     * کل مواردی که این شرکت‌کننده باید انجام بدهد: همه کارها + همه پرسش‌ها.
     */
    public function totalItems(): int
    {
        return $this->totalTasks() + $this->questionsCount();
    }

    /**
     * موارد انجام‌شده: کارهای تیک‌خورده + پرسش‌های پاسخ‌داده‌شده.
     */
    public function completedItems(): int
    {
        return $this->completedCount() + $this->answeredQuestionsCount();
    }

    /**
     * درصد پیشرفت: کارها و پرسش‌ها با هم شمرده می‌شوند.
     */
    public function progressPercent(): int
    {
        $total = $this->totalItems();

        if ($total === 0) {
            return 0;
        }

        return (int) round(min(1, $this->completedItems() / $total) * 100);
    }

    /**
     * Days (day numbers) where every task is completed and every question answered.
     *
     * @return array<int, int>
     */
    public function completedDays(): array
    {
        $days = [];

        foreach ($this->dayBreakdown() as $day => $row) {
            if ($row['complete']) {
                $days[] = (int) $day;
            }
        }

        return $days;
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
     * هر روز هم کارهایش شمرده می‌شود و هم پرسش‌هایش؛ روزی کامل است که
     * همه کارهای آن تیک خورده باشند و به همه پرسش‌هایش پاسخ داده شده باشد.
     *
     * @return array<int, array{total: int, done: int, tasks_total: int, tasks_done: int, questions_total: int, questions_done: int, complete: bool, is_today: bool, is_past: bool}>
     */
    public function dayBreakdown(): array
    {
        $times = $this->completionTimes();
        $currentDay = $this->challenge->currentDayNumber();

        $tasksByDay = $this->challenge->tasks->groupBy(fn (ChallengeTask $task) => $task->day_number ?? 0);
        $questionsByDay = $this->challengeQuestions()->groupBy(fn (ChallengeQuestion $question) => $question->day_number ?? 0);

        $breakdown = [];

        foreach ($tasksByDay->keys()->merge($questionsByDay->keys())->unique()->sort()->values() as $day) {
            $tasks = $tasksByDay->get($day) ?? collect();
            $questions = $questionsByDay->get($day) ?? collect();

            $tasksDone = $tasks->filter(fn (ChallengeTask $task) => isset($times[(int) $task->id]))->count();
            $questionsDone = $questions->filter(fn (ChallengeQuestion $question) => $this->isQuestionAnswered($question))->count();

            $total = $tasks->count() + $questions->count();
            $done = $tasksDone + $questionsDone;

            $breakdown[(int) $day] = [
                'total' => $total,
                'done' => $done,
                'tasks_total' => $tasks->count(),
                'tasks_done' => $tasksDone,
                'questions_total' => $questions->count(),
                'questions_done' => $questionsDone,
                'complete' => $total > 0 && $done === $total,
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
     * تعداد موارد انجام‌شده (کار تیک‌خورده و پاسخ پرسش) در هر روز از آخرین :days روز: [Y-m-d => count].
     *
     * @return array<string, int>
     */
    public function activityByDay(int $days = 14): array
    {
        $activity = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $activity[Carbon::today()->subDays($i)->toDateString()] = 0;
        }

        $moments = array_values($this->completionTimes());

        foreach ($this->answersByQuestionId() as $answer) {
            $moments[] = $answer->updated_at;
        }

        foreach ($moments as $time) {
            $key = Carbon::parse($time)->toDateString();

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
        $total = $this->totalItems();
        $done = $this->completedItems();

        if ($total === 0) {
            return ['label' => 'بدون کار', 'classes' => 'bg-cream text-muted ring-line', 'tone' => 'muted'];
        }

        if ($done === 0) {
            return ['label' => 'شروع نکرده', 'classes' => 'bg-cream text-muted ring-line', 'tone' => 'muted'];
        }

        $currentDay = $this->challenge->currentDayNumber();
        $breakdown = $this->dayBreakdown();

        if ($currentDay !== null && ($breakdown[$currentDay]['complete'] ?? false)) {
            return ['label' => 'امروز را کامل انجام داد', 'classes' => 'bg-leaf/10 text-forest ring-leaf/30', 'tone' => 'leaf'];
        }

        $last = $this->lastActivityAt();

        if ($last && $last->greaterThanOrEqualTo(Carbon::today()->subDays(2))) {
            return ['label' => 'فعال', 'classes' => 'bg-jade/10 text-jade ring-jade/30', 'tone' => 'jade'];
        }

        return ['label' => 'بی‌فعال', 'classes' => 'bg-gold/15 text-ink ring-gold/50', 'tone' => 'gold'];
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
