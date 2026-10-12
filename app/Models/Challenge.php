<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'tagline',
    'description',
    'long_description',
    'contact_note',
    'emoji',
    'accent',
    'starts_on',
    'ends_on',
    'is_published',
    'sort_order',
])]
#[RouteKey('slug')]
class Challenge extends Model
{
    public const ACCENTS = [
        'violet' => 'بنفش',
        'teal' => 'فیروزه‌ای',
        'amber' => 'کهربایی',
        'rose' => 'سرخ',
        'sky' => 'آبی',
        'emerald' => 'سبز',
    ];

    /**
     * کلاس‌های رنگی هر چالش (literal تا Tailwind بتواند آن‌ها را پیدا کند).
     */
    public const ACCENT_STYLES = [
        'violet' => [
            'gradient' => 'from-violet-500 to-fuchsia-500',
            'soft' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'bar' => 'bg-violet-500',
            'stroke' => 'stroke-violet-500',
            'text' => 'text-violet-700',
            'bg' => 'bg-violet-50',
        ],
        'teal' => [
            'gradient' => 'from-teal-500 to-cyan-500',
            'soft' => 'bg-teal-50 text-teal-700 ring-teal-200',
            'bar' => 'bg-teal-500',
            'stroke' => 'stroke-teal-500',
            'text' => 'text-teal-700',
            'bg' => 'bg-teal-50',
        ],
        'amber' => [
            'gradient' => 'from-amber-500 to-orange-500',
            'soft' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'bar' => 'bg-amber-500',
            'stroke' => 'stroke-amber-500',
            'text' => 'text-amber-700',
            'bg' => 'bg-amber-50',
        ],
        'rose' => [
            'gradient' => 'from-rose-500 to-pink-500',
            'soft' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'bar' => 'bg-rose-500',
            'stroke' => 'stroke-rose-500',
            'text' => 'text-rose-700',
            'bg' => 'bg-rose-50',
        ],
        'sky' => [
            'gradient' => 'from-sky-500 to-indigo-500',
            'soft' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'bar' => 'bg-sky-500',
            'stroke' => 'stroke-sky-500',
            'text' => 'text-sky-700',
            'bg' => 'bg-sky-50',
        ],
        'emerald' => [
            'gradient' => 'from-emerald-500 to-teal-500',
            'soft' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'bar' => 'bg-emerald-500',
            'stroke' => 'stroke-emerald-500',
            'text' => 'text-emerald-700',
            'bg' => 'bg-emerald-50',
        ],
    ];

    /**
     * @return array<string, string>
     */
    public function style(): array
    {
        return self::ACCENT_STYLES[$this->accent] ?? self::ACCENT_STYLES['violet'];
    }

    public function styleValue(string $key): string
    {
        return $this->style()[$key] ?? self::ACCENT_STYLES['violet'][$key];
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ChallengeTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ChallengeTask::class)->orderBy('day_number')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class)->orderBy('name');
    }

    /**
     * @return HasMany<ChallengeTip, $this>
     */
    public function tips(): HasMany
    {
        return $this->hasMany(ChallengeTip::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ChallengeQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ChallengeQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * پرسش و پاسخ‌هایی که برای مخاطب نمایش داده می‌شوند.
     *
     * @return HasMany<ChallengeQuestion, $this>
     */
    public function publishedQuestions(): HasMany
    {
        return $this->questions()->where('is_published', true);
    }

    /**
     * @return HasMany<ChallengeAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ChallengeAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function longDescriptionHtml(): string
    {
        return RichText::toHtml($this->long_description);
    }

    /**
     * Get the tasks grouped by their day number.
     *
     * @return Collection<int|string, Collection<int, ChallengeTask>>
     */
    public function tasksByDay(): Collection
    {
        return $this->tasks->groupBy(fn (ChallengeTask $task) => $task->day_number ?? 0);
    }

    public function daysCount(): int
    {
        return $this->tasks->pluck('day_number')->filter()->unique()->count() ?: $this->tasks->count();
    }

    public function tasksCount(): int
    {
        return $this->tasks->count();
    }

    /**
     * Percentage of tasks that all participants have completed.
     */
    public function progressPercent(): int
    {
        $tasks = $this->tasks()->count();
        $participants = $this->participants()->count();

        if ($tasks === 0 || $participants === 0) {
            return 0;
        }

        $completed = TaskCompletion::query()
            ->whereIn('participant_id', $this->participants()->select('id'))
            ->count();

        return (int) round(min(1, $completed / ($tasks * $participants)) * 100);
    }

    public function status(): string
    {
        $today = Carbon::today();

        if ($this->starts_on && $today->lt($this->starts_on)) {
            return 'upcoming';
        }

        if ($this->ends_on && $today->gt($this->ends_on)) {
            return 'finished';
        }

        return 'active';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'upcoming' => 'به‌زودی شروع می‌شود',
            'finished' => 'به پایان رسیده',
            default => 'در حال اجرا',
        };
    }

    public function statusClasses(): string
    {
        return match ($this->status()) {
            'upcoming' => 'bg-amber-500/10 text-amber-700 ring-amber-500/20',
            'finished' => 'bg-slate-500/10 text-slate-600 ring-slate-500/20',
            default => 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20',
        };
    }

    /**
     * The current day number of the challenge, based on its start date.
     */
    public function currentDayNumber(): ?int
    {
        $daysCount = $this->daysCount();

        if ($daysCount === 0) {
            return null;
        }

        if (! $this->starts_on) {
            return $daysCount;
        }

        $passed = (int) $this->starts_on->startOfDay()->diffInDays(Carbon::today()->startOfDay(), false) + 1;

        return max(1, min($daysCount, $passed));
    }

    public function daysRemaining(): ?int
    {
        if (! $this->ends_on) {
            return null;
        }

        return max(0, (int) Carbon::today()->startOfDay()->diffInDays($this->ends_on->startOfDay(), false));
    }

    public function dateRangeLabel(): string
    {
        if (! $this->starts_on && ! $this->ends_on) {
            return 'بدون تاریخ مشخص';
        }

        if ($this->starts_on && $this->ends_on) {
            return fa_date($this->starts_on).' تا '.fa_date($this->ends_on);
        }

        return $this->starts_on
            ? 'شروع از '.fa_date($this->starts_on)
            : 'تا '.fa_date($this->ends_on);
    }

    public function publicUrl(): string
    {
        return route('challenges.show', $this);
    }

    /**
     * ساخت نام یکتای آدرس (slug)؛ اگر عنوان فارسی باشد از عنوان لاتین یا شماره استفاده می‌کند.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'challenge';
        }

        $slug = $base;
        $counter = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
