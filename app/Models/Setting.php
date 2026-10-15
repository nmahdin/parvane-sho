<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public const DEFAULT_MAINTENANCE_MESSAGE = 'سایت در حال به‌روزرسانی است؛ به‌زودی برمی‌گردیم.';

    /**
     * حالت تعمیر سایت فعال است؟
     */
    public static function maintenanceMode(): bool
    {
        return static::get('maintenance_mode', '0') === '1';
    }

    /**
     * پیام صفحه «در حال تعمیر».
     */
    public static function maintenanceMessage(): string
    {
        return trim((string) static::get('maintenance_message')) ?: self::DEFAULT_MAINTENANCE_MESSAGE;
    }

    /**
     * Site-wide settings with sensible defaults.
     *
     * @return array<string, string>
     */
    public static function site(): array
    {
        try {
            $stored = static::query()
                ->whereIn('key', ['site_name', 'site_tagline', 'site_about'])
                ->pluck('value', 'key');
        } catch (\Throwable) {
            // جدول تنظیمات هنوز ساخته نشده است (مثلاً قبل از اجرای مهاجرت‌ها)
            $stored = collect();
        }

        return [
            'site_name' => $stored['site_name'] ?? config('panel.site_name'),
            'site_tagline' => $stored['site_tagline'] ?? config('panel.site_tagline'),
            'site_about' => $stored['site_about'] ?? config('panel.site_about'),
        ];
    }
}
