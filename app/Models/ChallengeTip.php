<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['challenge_id', 'emoji', 'title', 'body', 'sort_order'])]
class ChallengeTip extends Model
{
    public const EMOJIS = ['💡', '⭐️', '⚠️', '🔥', '🎯', '🧠', '❤️', '📌', '🚫', '⏰'];

    protected function casts(): array
    {
        return [
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
}
