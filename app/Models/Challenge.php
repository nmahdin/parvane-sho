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
    'daily_release',
    'sort_order',
])]
#[RouteKey('slug')]
class Challenge extends Model
{
    /**
     * رنگ‌های قابل انتخاب برای هر چالش — همه از پالت سایت.
     */
    public const ACCENTS = [
        'jade' => 'یشمی',
        'forest' => 'جنگلی',
        'leaf' => 'برگی',
        'gold' => 'طلایی',
        'sage' => 'مریم‌گلی',
        'olive' => 'زیتونی',
    ];

    /**
     * کلاس‌های رنگی هر چالش (literal تا Tailwind بتواند آن‌ها را پیدا کند).
     */
    public const ACCENT_STYLES = [
        'jade' => [
            'gradient' => 'from-forest to-jade',
            'soft' => 'bg-jade/10 text-jade ring-jade/30',
            'bar' => 'bg-jade',
            'stroke' => 'stroke-jade',
            'text' => 'text-jade',
            'bg' => 'bg-jade/10',
        ],
        'forest' => [
            'gradient' => 'from-forest to-leaf',
            'soft' => 'bg-forest/10 text-forest ring-forest/30',
            'bar' => 'bg-forest',
            'stroke' => 'stroke-forest',
            'text' => 'text-forest',
            'bg' => 'bg-forest/10',
        ],
        'leaf' => [
            'gradient' => 'from-leaf to-jade',
            'soft' => 'bg-leaf/10 text-leaf ring-leaf/30',
            'bar' => 'bg-leaf',
            'stroke' => 'stroke-leaf',
            'text' => 'text-leaf',
            'bg' => 'bg-leaf/10',
        ],
        'gold' => [
            'gradient' => 'from-gold to-leaf',
            'soft' => 'bg-gold/15 text-ink ring-gold/50',
            'bar' => 'bg-gold',
            'stroke' => 'stroke-gold',
            'text' => 'text-forest',
            'bg' => 'bg-gold/15',
        ],
        'sage' => [
            'gradient' => 'from-jade to-forest',
            'soft' => 'bg-line/60 text-forest ring-line',
            'bar' => 'bg-jade',
            'stroke' => 'stroke-jade',
            'text' => 'text-forest',
            'bg' => 'bg-line/60',
        ],
        'olive' => [
            'gradient' => 'from-leaf to-forest',
            'soft' => 'bg-leaf/15 text-forest ring-leaf/40',
            'bar' => 'bg-leaf',
            'stroke' => 'stroke-leaf',
            'text' => 'text-forest',
            'bg' => 'bg-leaf/15',
        ],
    ];

    /**
     * چالش‌های قدیمی که رنگشان از پالت قبلی ذخیره شده است.
     *
     * @var array<string, string>
     */
    public const LEGACY_ACCENTS = [
        'violet' => 'jade',
        'teal' => 'jade',
        'emerald' => 'leaf',
        'sky' => 'forest',
        'amber' => 'gold',
        'rose' => 'gold',
        'pink' => 'gold',
        'indigo' => 'forest',
        'purple' => 'jade',
        'green' => 'leaf',
        'cyan' => 'jade',
    ];

    public function accentKey(): string
    {
        $accent = (string) $this->accent;

        if (array_key_exists($accent, self::ACCENT_STYLES)) {
            return $accent;
        }

        return self::LEGACY_ACCENTS[$accent] ?? 'jade';
    }

    /**
     * @return array<string, string>
     */
    public function style(): array
    {
        return self::ACCENT_STYLES[$this->accentKey()];
    }

    public function styleValue(string $key): string
    {
        return $this->style()[$key] ?? self::ACCENT_STYLES['jade'][$key];
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_published' => 'boolean',
            'daily_release' => 'boolean',
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

    /**
     * پرسش‌ها گروه‌بندی‌شده بر اساس روزی که برایشان تعیین شده است.
     *
     * @return Collection<int|string, Collection<int, ChallengeQuestion>>
     */
    public function questionsByDay(bool $onlyPublished = false): Collection
    {
        return $this->questions
            ->when($onlyPublished, fn (Collection $questions) => $questions->where('is_published', true))
            ->groupBy(fn (ChallengeQuestion $question) => $question->day_number ?? 0);
    }

    /**
     * همه روزهای چالش که محتوا دارند (کار یا پرسش)، به ترتیب.
     * روز صفر یعنی «محتواهای بدون روز».
     *
     * @return Collection<int, int>
     */
    public function contentDayNumbers(): Collection
    {
        return $this->tasks->pluck('day_number')
            ->merge($this->questions->pluck('day_number'))
            ->filter()
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * کارها و پرسش‌های هر روز، کنار هم و به ترتیب روز.
     * روز صفر یعنی محتواهایی که به روزی نسبت داده نشده‌اند.
     *
     * @return Collection<int|string, array{tasks: Collection<int, ChallengeTask>, questions: Collection<int, ChallengeQuestion>}>
     */
    public function contentByDay(bool $onlyPublishedQuestions = false): Collection
    {
        $tasks = $this->tasksByDay();
        $questions = $this->questionsByDay($onlyPublishedQuestions);

        return $tasks->keys()
            ->merge($questions->keys())
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn ($day) => [
                $day => [
                    'tasks' => $tasks->get($day) ?? collect(),
                    'questions' => $questions->get($day) ?? collect(),
                ],
            ]);
    }

    /**
     * تعداد روزهای چالش؛ پرسش‌ها هم مثل کارها شمرده می‌شوند، پس روزی که فقط
     * پرسش دارد هم یک روز از چالش است.
     */
    public function daysCount(): int
    {
        $days = $this->contentDayNumbers();

        return $days->isNotEmpty() ? $days->count() : $this->tasksCount();
    }

    /**
     * تعداد روزهایی که محتوا دارند (کار یا پرسش).
     */
    public function contentDaysCount(): int
    {
        return $this->contentDayNumbers()->count();
    }

    public function tasksCount(): int
    {
        return (int) ($this->tasks_count ?? $this->tasks->count());
    }

    /**
     * تعداد پرسش‌های چالش (با احتساب با withCount شمارش‌شده، بدون کوئری اضافه).
     */
    public function questionsCount(): int
    {
        return (int) ($this->questions_count ?? $this->questions->count());
    }

    /**
     * پرسش‌ها مثل کارها شمرده می‌شوند؛ این عدد مجموع کارها و پرسش‌های چالش است.
     */
    public function contentCount(): int
    {
        return $this->tasksCount() + $this->questionsCount();
    }

    /**
     * تعداد کار و پرسش یک روز؛ برای نمایش شمارش‌ها در کارت هر روز.
     *
     * @return array{tasks: int, questions: int, total: int}
     */
    public function dayCounts(?int $dayNumber): array
    {
        $tasks = $this->tasks->where('day_number', $dayNumber)->count();
        $questions = $this->questions->where('day_number', $dayNumber)->count();

        return ['tasks' => $tasks, 'questions' => $questions, 'total' => $tasks + $questions];
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
            'upcoming' => 'bg-gold/15 text-ink ring-gold/50',
            'finished' => 'bg-line/60 text-muted ring-line',
            default => 'bg-leaf/10 text-forest ring-leaf/30',
        };
    }

    /**
     * آیا آزادسازی روزبه‌روز فعال است؟ (روزهای بعدی قفل می‌مانند)
     */
    public function releasesDaily(): bool
    {
        return (bool) $this->daily_release;
    }

    /**
     * تاریخ روز اول چالش: تاریخ شروع، و اگر ثبت نشده باشد روز ساخت چالش.
     */
    public function dayOneDate(): Carbon
    {
        return ($this->starts_on?->copy() ?? $this->created_at?->copy() ?? Carbon::today())->startOfDay();
    }

    public function hasStarted(): bool
    {
        return $this->dayOneDate()->lessThanOrEqualTo(Carbon::today()->startOfDay());
    }

    /**
     * تاریخی که روز مورد نظر باز (از قفل خارج) می‌شود. null یعنی همیشه باز است.
     */
    public function dayUnlockDate(?int $dayNumber): ?Carbon
    {
        if (! $dayNumber || $dayNumber < 1 || ! $this->releasesDaily()) {
            return null;
        }

        return $this->dateForDay($dayNumber);
    }

    /**
     * تاریخ میلادی یک روز از چالش (روز ۱ = روز اول چالش)، بدون توجه به قفل بودن.
     */
    public function dateForDay(int $dayNumber): Carbon
    {
        return $this->dayOneDate()->addDays(max(1, $dayNumber) - 1);
    }

    /**
     * شماره روز چالش برای یک تاریخ مشخص (روز ۱ = روز اول چالش).
     */
    public function dayNumberForDate(Carbon|\DateTimeInterface|string $date): int
    {
        $target = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfDay();

        return max(1, (int) $this->dayOneDate()->diffInDays($target, false) + 1);
    }

    /**
     * شماره روز امروز بر اساس تاریخ شروع چالش (بدون توجه به این که محتوا دارد یا نه).
     */
    public function todayDayNumber(): ?int
    {
        if (! $this->hasStarted()) {
            return null;
        }

        return max(1, (int) $this->dayOneDate()->diffInDays(Carbon::today()->startOfDay(), false) + 1);
    }

    /**
     * تعداد روزهایی که چالش تا تاریخ پایانش دارد.
     */
    public function plannedDaysCount(): int
    {
        if (! $this->ends_on) {
            return 0;
        }

        return max(0, (int) $this->dayOneDate()->diffInDays($this->ends_on->copy()->startOfDay(), false) + 1);
    }

    /**
     * روزهایی که در پنل برای انتخاب سریع نمایش داده می‌شوند: از روز ۱ تا
     * آخرین روزی که محتوا دارد، یا تاریخ پایان چالش است، یا امروز چالش است.
     *
     * @return Collection<int, int>
     */
    public function selectableDays(): Collection
    {
        $last = max(
            1,
            (int) ($this->contentDayNumbers()->last() ?? 0),
            (int) ($this->todayDayNumber() ?? 0),
            $this->plannedDaysCount()
        );

        return collect(range(1, min(365, $last)));
    }

    /**
     * برچسب تاریخ یک روز، مثل «سه‌شنبه، ۲۱ مهر ۱۴۰۵».
     */
    public function dayDateLabel(?int $dayNumber): ?string
    {
        if (! $dayNumber || $dayNumber < 1) {
            return null;
        }

        return fa_date($this->dateForDay($dayNumber), true);
    }

    public function isDayUnlocked(?int $dayNumber): bool
    {
        $unlockDate = $this->dayUnlockDate($dayNumber);

        return $unlockDate === null || $unlockDate->lessThanOrEqualTo(Carbon::today()->startOfDay());
    }

    /**
     * آیا این کار در روزی است که برای شرکت‌کننده‌ها هنوز قفل است؟
     */
    public function isTaskLocked(ChallengeTask $task): bool
    {
        return ! $this->isDayUnlocked($task->day_number);
    }

    /**
     * آیا این پرسش در روزی است که برای شرکت‌کننده‌ها هنوز قفل است؟
     */
    public function isQuestionLocked(ChallengeQuestion $question): bool
    {
        return ! $this->isDayUnlocked($question->day_number);
    }

    public function unlockLabel(?int $dayNumber): ?string
    {
        $unlockDate = $this->dayUnlockDate($dayNumber);

        if ($unlockDate === null || $unlockDate->lessThanOrEqualTo(Carbon::today()->startOfDay())) {
            return null;
        }

        return fa_date($unlockDate, true);
    }

    public function daysUntilUnlock(?int $dayNumber): ?int
    {
        $unlockDate = $this->dayUnlockDate($dayNumber);

        if ($unlockDate === null) {
            return null;
        }

        return max(0, (int) Carbon::today()->startOfDay()->diffInDays($unlockDate, false));
    }

    /**
     * تعداد روزهایی که همین حالا قفل هستند.
     */
    public function lockedDaysCount(): int
    {
        if (! $this->releasesDaily()) {
            return 0;
        }

        return $this->contentDayNumbers()
            ->reject(fn (int $day) => $this->isDayUnlocked($day))
            ->count();
    }

    /**
     * تاریخ باز شدن نزدیک‌ترین روز قفل‌شده.
     */
    public function nextUnlockDate(): ?Carbon
    {
        if (! $this->releasesDaily()) {
            return null;
        }

        $dates = $this->contentDayNumbers()
            ->map(fn (int $day) => $this->dayUnlockDate($day))
            ->filter(fn (?Carbon $date) => $date && $date->greaterThan(Carbon::today()->startOfDay()))
            ->sort();

        return $dates->first();
    }

    /**
     * شماره روز جاری چالش؛ اگر چالش شروع نشده باشد null برمی‌گردد.
     */
    public function currentDayNumber(): ?int
    {
        $daysCount = $this->daysCount();

        if ($daysCount === 0 || ! $this->hasStarted()) {
            return null;
        }

        $passed = (int) $this->dayOneDate()->diffInDays(Carbon::today()->startOfDay(), false) + 1;

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
