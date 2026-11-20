<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChallengeFileController extends Controller
{
    /**
     * دانلود فایل‌های ضمیمه چالش. فایل‌ها بیرون از پوشه عمومی ذخیره می‌شوند.
     */
    public function show(Challenge $challenge, ChallengeAttachment $attachment): StreamedResponse
    {
        abort_unless((int) $attachment->challenge_id === (int) $challenge->id, 404);
        abort_unless($challenge->is_published, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $attachment->increment('downloads');

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
