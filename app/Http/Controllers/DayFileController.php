<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\DayFile;
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
    public function show(Challenge $challenge, DayFile $dayFile): BinaryFileResponse
    {
        abort_unless((int) $dayFile->challenge_id === (int) $challenge->id, 404);
        abort_unless($challenge->is_published, 404);
        abort_unless($challenge->isDayUnlocked((int) $dayFile->day_number), 404);
        abort_unless(Storage::disk('local')->exists($dayFile->path), 404);

        $dayFile->increment('downloads');

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
}
