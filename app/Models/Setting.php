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
     * متن‌های پیش‌فرض صفحه اصلی (قابل مدیریت از پنل).
     */
    public const HOME_DEFAULTS = [
        'home_hero_line' => 'هر روز یک قدم کوچک، یک تغییر واقعی',
        'home_cta_primary' => 'دیدن چالش‌ها',
        'home_cta_secondary' => 'کد اختصاصی من را دارم',
        'home_why_title' => 'چرا با هم و در چالش؟',
        'home_banner_title' => 'آماده‌ای امروز شروع کنی؟',
        'home_banner_text' => 'چالش‌ها را ببین، چالشی که به تو نزدیک است را انتخاب کن و همان امروز اولین قدم را بردار.',
    ];

    /**
     * محتوای صفحه اصلی: ذخیره‌شده‌ها روی پیش‌فرض‌ها.
     *
     * @return array<string, string>
     */
    public static function home(): array
    {
        $content = self::HOME_DEFAULTS;

        foreach (array_keys(self::HOME_DEFAULTS) as $key) {
            $stored = trim((string) static::get($key, ''));

            if ($stored !== '') {
                $content[$key] = $stored;
            }
        }

        return $content;
    }

    /**
     * رنگ‌های پیش‌فرض سایت (همان توکن‌های resources/css/app.css).
     */
    public const THEME_DEFAULTS = [
        'jade' => '#659287',
        'forest' => '#4E7268',
        'leaf' => '#B8C9A3',
        'gold' => '#F2DDA8',
        'cream' => '#F6F6F2',
        'shell' => '#EFF1E6',
        'canvas' => '#F6F7F1',
        'line' => '#C9D2B9',
    ];

    /**
     * رنگ‌های فعلی سایت: مقادیر ذخیره‌شده روی پیش‌فرض‌ها، فقط هگز معتبر.
     *
     * @return array<string, string>
     */
    public static function themeColors(): array
    {
        $colors = self::THEME_DEFAULTS;

        try {
            $stored = json_decode((string) static::get('theme_colors', '{}'), true);
        } catch (\Throwable) {
            $stored = null;
        }

        if (is_array($stored)) {
            foreach (self::THEME_DEFAULTS as $key => $default) {
                $value = $stored[$key] ?? null;

                if (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    $colors[$key] = strtoupper($value);
                }
            }
        }

        return $colors;
    }

    /**
     * CSS متغیرهای رنگ برای تزریق در <head>؛ وقتی چیزی تغییر نکرده null است.
     */
    public static function themeCss(): ?string
    {
        $colors = static::themeColors();

        if ($colors === self::THEME_DEFAULTS) {
            return null;
        }

        $mix = static function (string $hex, string $with, int $percentOfWith): string {
            $channel = static fn (string $c, int $i): int => hexdec(substr($c, $i, 2));
            $out = '';

            for ($i = 1; $i <= 5; $i += 2) {
                $a = $channel($hex, $i);
                $b = $channel($with, $i);
                $v = (int) round($a + ($b - $a) * $percentOfWith / 100);
                $out .= str_pad(strtoupper(dechex(max(0, min(255, $v)))), 2, '0', STR_PAD_LEFT);
            }

            return '#'.$out;
        };

        $jade = $colors['jade'];
        $forest = $colors['forest'];

        return ':root{'
            .implode('; ', [
                '--color-jade: '.$jade,
                '--color-forest: '.$forest,
                '--color-leaf: '.$colors['leaf'],
                '--color-gold: '.$colors['gold'],
                '--color-cream: '.$colors['cream'],
                '--color-shell: '.$colors['shell'],
                '--color-canvas: '.$colors['canvas'],
                '--color-line: '.$colors['line'],
                '--color-brand-50: '.$mix($jade, '#FFFFFF', 92),
                '--color-brand-100: '.$mix($jade, '#FFFFFF', 82),
                '--color-brand-200: '.$mix($jade, '#FFFFFF', 65),
                '--color-brand-300: '.$mix($jade, '#FFFFFF', 45),
                '--color-brand-400: '.$mix($jade, '#FFFFFF', 25),
                '--color-brand-500: '.$jade,
                '--color-brand-600: '.$mix($jade, '#000000', 10),
                '--color-brand-700: '.$forest,
                '--color-brand-800: '.$mix($forest, '#000000', 15),
                '--color-brand-900: '.$mix($forest, '#000000', 30),
            ]).';}';
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
