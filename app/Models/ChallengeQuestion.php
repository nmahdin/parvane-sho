<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['challenge_id', 'day_number', 'question', 'answer', 'asked_by', 'is_published', 'sort_order'])]
class ChallengeQuestion extends Model
{
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

    public function dayLabel(): string
    {
        return $this->day_number ? 'روز '.fa_num($this->day_number) : 'پرسش‌های کلی';
    }
}
