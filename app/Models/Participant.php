<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['challenge_id', 'name', 'contact', 'note', 'token', 'last_seen_at'])]
#[RouteKey('token')]
class Participant extends Model
{
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
    public static function generateToken(int $length = 16): string
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

    public function markSeen(): void
    {
        $this->forceFill(['last_seen_at' => Carbon::now()])->saveQuietly();
    }

    public function dashboardUrl(): string
    {
        return route('participant.show', $this);
    }
}
