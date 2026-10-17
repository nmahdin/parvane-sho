<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\DayFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * دانلود یا پخش فایل‌های هر روز.
 *
 * فایل‌ها بیرون از پوشه عمومی‌اند؛ صوتی‌ها با `inline` فرستاده می‌شوند تا
 * پخش‌کننده اختصاصی سایت بتواند همان‌جا پخش کند (و مرورگر به دانلود نیفتد)،
 * بقیه فایل‌ها به‌صورت دانلود فرستاده می‌شوند.
 *
 * محتوای هر روز تا آزاد شدن همان روز پنهان است، پس فایل‌های روزهای قفل
 * هم برای شرکت‌کننده‌ها در دسترس نیستند (۴۰۴).
 */
class DayFileController extends Controller
{
    public function show(Request $request, Challenge $challenge, DayFile $dayFile): BinaryFileResponse
    {
        abort_unless((int) $dayFile->challenge_id === (int) $challenge->id, 404);
        abort_unless($challenge->is_published, 404);
        abort_unless($challenge->isDayUnlocked((int) $dayFile->day_number), 404);
        abort_unless(Storage::disk('local')->exists($dayFile->path), 404);

        // دانلود فقط برای فایل‌های غیرصوتی شمرده می‌شود. پخش صوت را
        // پخش‌کنندهٔ سایت با beacon دقیق می‌شمارد (فقط وقتی کاربر واقعاً
        // دکمهٔ پخش را می‌زند) — چون preload مرورگر هم بایت‌های اول را
        // می‌گیرد و شمار سمت سرور، بازدید صفحه را به‌جای پخش حساب می‌کرد.
        if (! $dayFile->is_audio) {
            $dayFile->increment('downloads');
        }

        $response = response()->file(Storage::disk('local')->path($dayFile->path), [
            'Content-Type' => $dayFile->mime ?: 'application/octet-stream',
        ]);

        // صوتی‌ها inline می‌آیند تا پخش‌کننده خود سایت همان‌جا پخش کند؛
        // بقیه فایل‌ها دانلود می‌شوند.
        $disposition = $dayFile->is_audio ? 'inline' : 'attachment';

        $response->headers->set(
            'Content-Disposition',
            $disposition.'; filename="'.rawurlencode($dayFile->original_name).'"'
        );

        return $response;
    }

    /**
     * شمار پخش واقعی: پخش‌کنندهٔ صوت وقتی کاربر دکمهٔ پخش را می‌زند
     * (یک‌بار در هر بار باز شدن صفحه) این beacon را می‌فرستد.
     */
    public function play(Request $request, Challenge $challenge, DayFile $dayFile): \Illuminate\Http\Response
    {
        abort_unless((int) $dayFile->challenge_id === (int) $challenge->id, 404);
        abort_unless($challenge->is_published, 404);
        abort_unless($dayFile->is_audio, 404);
        abort_unless($challenge->isDayUnlocked((int) $dayFile->day_number), 404);

        $dayFile->increment('plays');

        return response()->noContent();
    }
}
