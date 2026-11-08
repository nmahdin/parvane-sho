<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

#[Fillable(['challenge_id', 'day_number', 'question', 'answer', 'asked_by', 'is_published', 'sort_order'])]
class ChallengeQuestion extends Model
{
    /**
     * پرسش‌ها به‌صورت پیش‌فرض در صفحه چالش نمایش داده می‌شوند.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_published' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'is_published' => 'boolean',
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
     * @return HasMany<ChallengeQuestionAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ChallengeQuestionAnswer::class)->latest('updated_at');
    }

    public function dayLabel(): string
    {
        return $this->day_number ? 'روز '.fa_num($this->day_number) : 'پرسش‌های کلی';
    }

    /**
     * چه کسانی به این پرسش پاسخ داده‌اند و چه کسانی نداده‌اند.
     *
     * @param  Collection<int, Participant>  $participants
     * @return array{answered: list<array{participant: Participant, answered_at: ?Carbon}>, pending: list<Participant>, total: int}
     */
    public function statusForParticipants(Collection $participants): array
    {
        $answers = $this->relationLoaded('answers') ? $this->answers : $this->answers()->get();

        $answered = [];
        $pending = [];

        foreach ($participants as $participant) {
            $answer = $answers->firstWhere('participant_id', $participant->getKey());

            if ($answer) {
                $answered[] = ['participant' => $participant, 'answered_at' => $answer->updated_at];

                continue;
            }

            $pending[] = $participant;
        }

        return ['answered' => $answered, 'pending' => $pending, 'total' => $participants->count()];
    }
}
