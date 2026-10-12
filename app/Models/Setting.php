<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

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

    /**
     * The hashed admin panel password. Null when the default (env) password is used.
     */
    public static function panelPasswordHash(): ?string
    {
        return static::get('panel_password_hash');
    }

    public static function setPanelPassword(string $plainPassword): void
    {
        static::put('panel_password_hash', Hash::make($plainPassword));
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
