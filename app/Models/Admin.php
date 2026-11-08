<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * مدیر پنل. دو نوع مدیر داریم:
 * - مدیر اصلی (is_super): همه چالش‌ها، شرکت‌کننده‌ها و مدیران را می‌بیند و مدیر جدید می‌سازد.
 * - مدیر چالش: فقط چالش‌های خودش و شرکت‌کننده‌های همان چالش‌ها را می‌بیند.
 */
#[Fillable(['name', 'username', 'password', 'is_super', 'is_active', 'last_login_at'])]
class Admin extends Authenticatable
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_super' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_super' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSuper(): bool
    {
        return (bool) $this->is_super;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function roleLabel(): string
    {
        return $this->isSuper() ? 'مدیر اصلی' : 'مدیر چالش';
    }

    public function roleClasses(): string
    {
        return $this->isSuper()
            ? 'bg-gold/15 text-ink ring-gold/50'
            : 'bg-jade/10 text-jade ring-jade/30';
    }

    /**
     * @return HasMany<Challenge, $this>
     */
    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    /**
     * چالش‌هایی که این مدیر اجازه دیدنشان را دارد.
     *
     * @return Builder<Challenge>
     */
    public function visibleChallenges(): Builder
    {
        $query = Challenge::query();

        return $this->isSuper() ? $query : $query->where('admin_id', $this->getKey());
    }

    /**
     * شناسه چالش‌های قابل دسترس برای این مدیر.
     *
     * @return array<int, int>
     */
    public function visibleChallengeIds(): array
    {
        if ($this->isSuper()) {
            return Challenge::query()->pluck('id')->all();
        }

        return Challenge::query()->where('admin_id', $this->getKey())->pluck('id')->all();
    }

    public function canAccessChallenge(?Challenge $challenge): bool
    {
        if (! $challenge) {
            return false;
        }

        return $this->isSuper() || (int) $challenge->admin_id === (int) $this->getKey();
    }

    public function canAccessParticipant(?Participant $participant): bool
    {
        return $participant !== null && $this->canAccessChallenge($participant->challenge);
    }

    /**
     * فقط مدیر اصلی‌ها؛ برای صفحه مدیریت مدیران.
     */
    public function scopeSuper(Builder $query): Builder
    {
        return $query->where('is_super', true);
    }
}
