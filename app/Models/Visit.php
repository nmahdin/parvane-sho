<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * یک بازدید صفحه در سمت عمومی سایت (نه پنل).
 *
 * برای آمار بازدید روزانه/ماهانه و تفکیک هر چالش استفاده می‌شود؛
 * هیچ داده شخصی (IP یا اثر انگشت مرورگر) ذخیره نمی‌شود.
 */
#[Fillable(['challenge_id', 'participant_id', 'type', 'day_number', 'visited_at'])]
class Visit extends Model
{
    public const TYPE_HOME = 'home';
    public const TYPE_CHALLENGE = 'challenge';
    public const TYPE_DAY = 'day';
    public const TYPE_DASHBOARD = 'dashboard';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'visited_at' => 'datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * ثبت یک بازدید؛ درخواست‌های رفرش جزئی پنل (بدون رفرش صفحه) شمرده
     * نمی‌شوند تا یک ماندن در صفحه چند بار حساب نشود.
     */
    public static function record(Request $request, string $type, ?Challenge $challenge = null, ?int $dayNumber = null, ?Participant $participant = null): void
    {
        if ($request->headers->has('X-Panel-Refresh')) {
            return;
        }

        static::query()->create([
            'challenge_id' => $challenge?->getKey(),
            'participant_id' => $participant?->getKey(),
            'type' => $type,
            'day_number' => $dayNumber !== null && $dayNumber > 0 ? $dayNumber : null,
            'visited_at' => now(),
        ]);
    }

    /**
     * فقط چالش‌های یک مدیر (برای صفحه آمار پنل).
     */
    public function scopeVisibleToAdmin(Builder $query, Admin $admin): Builder
    {
        if ($admin->is_super) {
            return $query;
        }

        // مدیر عادی فقط آمار چالش‌های خودش را می‌بیند؛ بازدید صفحه اصلی
        // به چالش خاصی وابسته نیست و فقط در آمار مدیر کل می‌آید.
        return $query->whereIn('challenge_id', Challenge::query()->visibleToAdmin($admin)->select('id'));
    }
}
