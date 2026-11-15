<?php

namespace App\Support;

use App\Models\DayFile;

/**
 * سقف واقعی آپلود روی سرور.
 *
 * اپلیکیشن تا ۶۰ مگابایت را می‌پذیرد، اما PHP پیش از رسیدن درخواست به
 * Laravel آن را با `upload_max_filesize` و `post_max_size` محدود می‌کند
 * (پیش‌فرض بیشتر سرورها ۲ و ۸ مگابایت است). این کلاس همان دو مقدار را
 * می‌خواند تا هم پیام خطای درست نشان داده شود و هم مدیر بداند سرورش الان
 * چقدر را می‌پذیرد.
 */
class UploadLimits
{
    /**
     * جایگزین موقت سقف سرور (فقط برای آزمون).
     */
    protected static ?int $fake = null;

    /**
     * برای آزمون: سقف سرور را موقتاً جایگزین می‌کند (`null` = برگشت به php.ini).
     */
    public static function fake(?int $bytes): void
    {
        static::$fake = $bytes;
    }

    /**
     * تبدیل مقدار php.ini (مثل 8M یا 512K یا -1) به بایت؛ صفر یعنی نامحدود.
     */
    public static function toBytes(string|int|null $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value[0] === '-') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return (int) match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * بیشترین حجمی که سرور می‌پذیرد (بایت)؛ صفر یعنی نامحدود.
     */
    public static function serverMaxBytes(): int
    {
        if (static::$fake !== null) {
            return static::$fake;
        }

        $limits = array_filter([
            self::toBytes(ini_get('upload_max_filesize')),
            self::toBytes(ini_get('post_max_size')),
        ], fn (int $bytes) => $bytes > 0);

        return $limits === [] ? 0 : min($limits);
    }

    /**
     * سقف سرور از سقف اپلیکیشن (۶۰ مگابایت) کمتر است؟
     */
    public static function isBelowAppLimit(): bool
    {
        $server = self::serverMaxBytes();

        return $server > 0 && $server < DayFile::MAX_KILOBYTES * 1024;
    }

    /**
     * سقف سرور به واحد خوانای فارسی (برای نمایش در پنل).
     */
    public static function serverMaxForHumans(): string
    {
        $bytes = self::serverMaxBytes();

        if ($bytes <= 0) {
            return 'نامحدود';
        }

        $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return fa_num(round($bytes / (1024 ** $power), $power === 0 ? 0 : 1)).' '.$units[$power];
    }

    /**
     * پیام هشدار برای فرم آپلود — فقط وقتی سرور کمتر از ۶۰ مگابایت می‌پذیرد.
     */
    public static function warning(): ?string
    {
        if (! self::isBelowAppLimit()) {
            return null;
        }

        return 'سرور این سایت الان حداکثر '.self::serverMaxForHumans()
            .' را می‌پذیرد (upload_max_filesize و post_max_size در php.ini).'
            .' برای آپلود تا ۶۰ مگابایت این دو مقدار را حداقل روی 64M و 70M بگذار.';
    }
}
