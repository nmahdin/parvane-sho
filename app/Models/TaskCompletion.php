<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['participant_id', 'challenge_task_id', 'completed_at'])]
class TaskCompletion extends Model
{
    /**
     * در پنل، مدیر چالش فقط تیک‌های چالش‌های خودش را می‌بیند.
     *
     * @param  Builder<TaskCompletion>  $query
     * @return Builder<TaskCompletion>
     */
    public function scopeVisibleToAdmin(Builder $query, ?Admin $admin): Builder
    {
        if (! $admin || $admin->isSuper()) {
            return $query;
        }

        return $query->whereHas('participant.challenge', fn (Builder $challenge) => $challenge->where('admin_id', $admin->getKey()));
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<ChallengeTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ChallengeTask::class, 'challenge_task_id');
    }
}
