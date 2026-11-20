<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تنظیمات یک روز از چالش: عنوان و توضیحات.
 * کارها، پرسش‌ها و نکته‌های هر روز در مدل‌های خودشان با `day_number` به روز
 * وصل می‌شوند؛ این مدل فقط برای عنوان و توضیحات روز است.
 */
#[Fillable(['challenge_id', 'day_number', 'title', 'description'])]
class ChallengeDay extends Model
{
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
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
     * آیا این روز عنوان یا توضیحی دارد؟
     */
    public function hasContent(): bool
    {
        return filled($this->title) || filled($this->description);
    }
}
