<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChallengeAttachmentController extends Controller
{
    /**
     * حجم مجاز فایل ضمیمه (کیلوبایت).
     */
    private const MAX_KILOBYTES = 8192;

    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:190'],
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES],
        ], [
            'file.required' => 'یک فایل انتخاب کنید (اگر فایل بزرگ است، حجم آن نباید بیشتر از ۸ مگابایت باشد).',
            'file.max' => 'حجم فایل نباید بیشتر از ۸ مگابایت باشد. برای فایل‌های بزرگ‌تر مقدار upload_max_filesize را در php.ini بالا ببرید.',
        ], [
            'title' => 'عنوان فایل',
            'file' => 'فایل',
        ]);

        $file = $validated['file'];
        $path = $file->store('challenge-files/'.$challenge->id, 'local');

        $challenge->attachments()->create([
            'title' => $validated['title'] ?: $file->getClientOriginalName(),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'sort_order' => (int) $challenge->attachments()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'فایل ضمیمه اضافه شد.');
    }

    public function update(Request $request, Challenge $challenge, ChallengeAttachment $attachment): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $attachment);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:190'],
        ], [
            'title.required' => 'عنوان فایل را وارد کنید.',
        ], [
            'title' => 'عنوان فایل',
        ]);

        $attachment->update(['title' => $validated['title']]);

        return back()->with('status', 'عنوان فایل ذخیره شد.');
    }

    public function destroy(Challenge $challenge, ChallengeAttachment $attachment): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $attachment);

        if (Storage::disk('local')->exists($attachment->path)) {
            Storage::disk('local')->delete($attachment->path);
        }

        $attachment->delete();

        return back()->with('status', 'فایل ضمیمه حذف شد.');
    }

    private function ensureBelongsToChallenge(Challenge $challenge, ChallengeAttachment $attachment): void
    {
        abort_unless((int) $attachment->challenge_id === (int) $challenge->id, 404);
    }
}
