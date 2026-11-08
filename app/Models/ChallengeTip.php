<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['challenge_id', 'day_number', 'emoji', 'title', 'body', 'sort_order'])]
class ChallengeTip extends Model
{
    public const EMOJIS = ['💡', '⭐️', '⚠️', '🔥', '🎯', '🧠', '❤️', '📌', '🚫', '⏰'];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * آیا این نکته به روز مشخصی تعلق دارد؟
     */
    public function hasDay(): bool
    {
        return $this->day_number !== null && $this->day_number > 0;
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
