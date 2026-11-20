<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

#[Fillable(['challenge_id', 'day_number', 'title', 'description', 'sort_order'])]
class ChallengeTask extends Model
{
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'sort_order' => 'integer',
        ];
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

    public function dayLabel(): string
    {
        return $this->day_number ? 'روز '.fa_num($this->day_number) : 'کارهای کلی';
    }

    /**
     * چه کسانی این کار را انجام داده‌اند و چه کسانی نداده‌اند.
     *
     * @param  Collection<int, Participant>  $participants
     * @return array{done: list<array{participant: Participant, completed_at: ?Carbon}>, pending: list<Participant>, total: int}
     */
    public function statusForParticipants(Collection $participants): array
    {
        $done = [];
        $pending = [];

        foreach ($participants as $participant) {
            $completion = $participant->completions->firstWhere('challenge_task_id', $this->getKey());

            if ($completion && $completion->completed_at !== null) {
                $done[] = ['participant' => $participant, 'completed_at' => $completion->completed_at];

                continue;
            }

            $pending[] = $participant;
        }

        return ['done' => $done, 'pending' => $pending, 'total' => $participants->count()];
    }
}
