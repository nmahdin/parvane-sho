<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * دیدن دمِ لاگ خطاها از خود پنل — فقط برای مدیر اصلی.
 *
 * وقتی آپلود یا ذخیره‌ای با کد ۵۰۰ شکست می‌خورد، متن کامل استثنا در
 * `storage/logs/laravel.log` ثبت می‌شود. به‌جای گشتن دنبال فایل لاگ روی
 * سرور، همان چند خط آخر اینجا خوانده می‌شود.
 */
class ErrorLogController extends Controller
{
    /**
     * بیشترین تعداد خطی که یک‌جا نشان داده می‌شود.
     */
    private const MAX_LINES = 400;

    /**
     * بیشترین بایتی که از انتهای لاگ خوانده می‌شود (لاگ می‌تواند بزرگ باشد).
     */
    private const MAX_BYTES = 256 * 1024;

    public function index(Request $request): Response
    {
        $limit = min(max((int) $request->query('lines', 120), 1), self::MAX_LINES);

        $path = storage_path('logs/laravel.log');

        if (! is_file($path) || filesize($path) === 0) {
            return $this->plain('لاگی در storage/logs/laravel.log ثبت نشده است.'."\n");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return $this->plain('فایل لاگ خوانده نشد (اجازهٔ خواندن ندارد).'."\n");
        }

        try {
            fseek($handle, 0, SEEK_END);
            $size = (int) ftell($handle);
            $length = min($size, $request->boolean('errors') ? self::MAX_BYTES * 4 : self::MAX_BYTES);
            fseek($handle, $size - $length);
            $chunk = (string) fread($handle, $length);
        } finally {
            fclose($handle);
        }

        $lines = preg_split('/\R/', $chunk) ?: [];
        $lines = array_values(array_filter($lines, fn ($line) => trim($line) !== ''));

        /* حالت «فقط خطاها»: خط‌های stacktrace کنار می‌روند و همان خط‌هایی
           می‌مانند که خودِ استثنا را می‌گویند (ERROR/CRITICAL/EMERGENCY)؛
           وقتی APP_DEBUG خاموش است، علت خطا فقط همین‌جا پیدا می‌شود. */
        if ($request->boolean('errors')) {
            $lines = array_values(array_filter(
                $lines,
                fn ($line) => (bool) preg_match('/\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $line)
            ));
        }

        $lines = array_slice($lines, -$limit);

        $title = $request->boolean('errors')
            ? '# '.fa_num(count($lines)).' خطای ثبت‌شده در storage/logs/laravel.log'
            : '# '.fa_num(count($lines)).' خط آخر از storage/logs/laravel.log';

        $header = $title.' (به‌روزرسانی: '.fa_num(date('Y-m-d H:i:s', (int) filemtime($path))).')'."\n";

        if ($lines === []) {
            return $this->plain($header."\nخطایی ثبت نشده است.\n");
        }

        return $this->plain($header."\n".implode("\n", $lines)."\n");
    }

    private function plain(string $text): Response
    {
        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
