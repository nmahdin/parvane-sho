<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Panel\Concerns\ResolvesChallengeDay;
use App\Models\Challenge;
use App\Models\DayFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * افزودن، ویرایش و حذف فایل و فایل صوتی هر روز از چالش.
 *
 * فایل‌ها بیرون از پوشه عمومی ذخیره می‌شوند و مثل بقیه محتوای روز، تا آزاد
 * شدن همان روز برای شرکت‌کننده‌ها پنهان می‌مانند.
 */
class DayFileController extends Controller
{
    use ResolvesChallengeDay;

    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $request->validate([
            'day_number' => ['required_without:date', 'nullable', 'integer', 'min:1', 'max:365'],
            'date' => $this->dateRules($challenge),
            'title' => ['nullable', 'string', 'max:190'],
            'file' => ['required', 'file', 'max:'.DayFile::MAX_AUDIO_KILOBYTES],
        ], [
            'day_number.required_without' => 'روز فایل را مشخص کنید.',
            'day_number.min' => 'شماره روز باید از ۱ شروع شود.',
            'file.required' => 'یک فایل انتخاب کنید.',
            'file.max' => 'حجم فایل نباید بیشتر از '.fa_num((int) (DayFile::MAX_AUDIO_KILOBYTES / 1024)).' مگابایت باشد. برای فایل‌های بزرگ‌تر مقدار upload_max_filesize را در php.ini بالا ببرید.',
        ], [
            'day_number' => 'روز',
            'date' => 'تاریخ روز',
            'title' => 'عنوان فایل',
            'file' => 'فایل',
        ]);

        $dayNumber = (int) $this->resolveDayNumber($request, $challenge);

        abort_if($dayNumber < 1, 422, 'روز فایل را مشخص کنید.');
        $file = $request->file('file');

        abort_if($file->getSize() > $this->maxBytesFor($file->getClientOriginalName(), $file->getClientMimeType()), 422,
            'حجم این فایل بیشتر از حد مجاز است.');

        $isAudio = DayFile::looksLikeAudio($file->getClientMimeType(), $file->getClientOriginalName());

        $path = $file->store('challenge-days/'.$challenge->id.'/day-'.$dayNumber, 'local');

        $challenge->dayFiles()->create([
            'day_number' => $dayNumber,
            'title' => $request->input('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'is_audio' => $isAudio,
            'sort_order' => $this->nextOrder($challenge, $dayNumber),
        ]);

        return back()->with(
            'status',
            ($isAudio ? 'فایل صوتی' : 'فایل').' برای روز '.fa_num($dayNumber).' اضافه شد.'
                .' در سایت '.($isAudio ? 'با پخش‌کنندهٔ خود سایت پخش می‌شود.' : 'برای دانلود گذاشته می‌شود.')
        );
    }

    public function update(Request $request, Challenge $challenge, DayFile $dayFile): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $dayFile);

        $request->validate([
            'date' => $this->dateRules($challenge),
            'day_number' => ['nullable', 'integer', 'min:1', 'max:365'],
            'title' => ['required', 'string', 'max:190'],
        ], [
            'title.required' => 'عنوان فایل را وارد کنید.',
        ], [
            'day_number' => 'روز',
            'date' => 'تاریخ روز',
            'title' => 'عنوان فایل',
        ]);

        $dayNumber = (int) ($this->resolveDayNumber($request, $challenge) ?: $dayFile->day_number);

        $dayFile->update([
            'day_number' => $dayNumber,
            'title' => $request->input('title'),
        ]);

        return back()->with('status', 'فایل ذخیره شد.');
    }

    public function destroy(Challenge $challenge, DayFile $dayFile): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $dayFile);

        if (Storage::disk('local')->exists($dayFile->path)) {
            Storage::disk('local')->delete($dayFile->path);
        }

        $dayFile->delete();

        return back()->with('status', 'فایل حذف شد.');
    }

    /**
     * سقف حجم: فایل‌های صوتی بیشتر، بقیه فایل‌ها ۸ مگابایت.
     */
    private function maxBytesFor(?string $filename, ?string $mime): int
    {
        $kilobytes = DayFile::looksLikeAudio($mime, $filename)
            ? DayFile::MAX_AUDIO_KILOBYTES
            : DayFile::MAX_KILOBYTES;

        return $kilobytes * 1024;
    }

    private function nextOrder(Challenge $challenge, int $dayNumber): int
    {
        return (int) $challenge->dayFiles()->where('day_number', $dayNumber)->max('sort_order') + 1;
    }

    private function ensureBelongsToChallenge(Challenge $challenge, DayFile $dayFile): void
    {
        abort_unless((int) $dayFile->challenge_id === (int) $challenge->id, 404);
    }
}
