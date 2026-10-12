<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
