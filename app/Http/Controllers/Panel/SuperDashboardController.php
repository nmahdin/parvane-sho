<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Challenge;
use App\Models\ChallengeAttachment;
use App\Models\ChallengeQuestionAnswer;
use App\Models\DayFile;
use App\Models\Participant;
use App\Models\TaskCompletion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * داشبورد مدیر کل (فقط مدیر اصلی):
 * مدیریت فایل‌های آپلودشدهٔ کل سایت، دیدن ارتباط داده‌ها (هر چالش با
 * روزها/کارها/پرسش‌ها/نکته‌ها/فایل‌ها/شرکت‌کننده‌ها) و حجم مصرفی.
 */
class SuperDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', 'all');

        // مدیریت فایل‌ها: فایل‌های روزهای همه چالش‌ها
        $dayFiles = DayFile::query()
            ->with('challenge:id,title,slug')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhere('original_name', 'like', '%'.$search.'%');
                });
            })
            ->when($type === 'audio', fn ($query) => $query->where('is_audio', true))
            ->when($type === 'image', fn ($query) => $query->where('is_audio', false)
                ->where(function ($inner): void {
                    $inner->where('mime', 'like', 'image/%');

                    foreach (DayFile::IMAGE_EXTENSIONS as $extension) {
                        $inner->orWhere('original_name', 'like', '%.'.$extension);
                    }
                }))
            ->when($type === 'other', fn ($query) => $query->where('is_audio', false)
                ->whereNot('mime', 'like', 'image/%'))
            ->orderByDesc('size')
            ->paginate(20)
            ->withQueryString();

        $attachments = ChallengeAttachment::query()
            ->with('challenge:id,title,slug')
            ->orderByDesc('size')
            ->get();

        // حجم مصرفی
        $audio = DayFile::query()->where('is_audio', true)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(size), 0) as bytes')->first();
        $images = DayFile::query()->where('is_audio', false)->where('mime', 'like', 'image/%')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(size), 0) as bytes')->first();
        $otherFiles = DayFile::query()->where('is_audio', false)->whereNot('mime', 'like', 'image/%')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(size), 0) as bytes')->first();
        $attachmentStats = ChallengeAttachment::query()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(size), 0) as bytes')->first();

        $totalBytes = (int) ($audio->bytes + $images->bytes + $otherFiles->bytes + $attachmentStats->bytes);
        $totalFiles = (int) ($audio->total + $images->total + $otherFiles->total + $attachmentStats->total);

        $storagePath = storage_path('app');
        $diskFree = @disk_free_space($storagePath) ?: 0;
        $diskTotal = @disk_total_space($storagePath) ?: 0;

        $dbBytes = null;
        if (config('database.default') === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');

            if (is_string($dbPath) && is_file($dbPath)) {
                $dbBytes = filesize($dbPath);
            }
        }

        // ارتباط‌ها: هر چالش با چه چیزهایی گره خورده است
        $challenges = Challenge::query()
            ->with('admin:id,username')
            ->withCount(['days', 'tasks', 'questions', 'tips', 'participants', 'dayFiles', 'attachments'])
            ->withSum('dayFiles', 'size')
            ->withSum('attachments', 'size')
            ->orderBy('id')
            ->get();

        $totals = [
            'answers' => ChallengeQuestionAnswer::query()->count(),
            'completions' => TaskCompletion::query()->count(),
            'participants' => Participant::query()->count(),
            'admins' => Admin::query()->count(),
        ];

        return view('panel.super', [
            'dayFiles' => $dayFiles,
            'attachments' => $attachments,
            'search' => $search,
            'type' => $type,
            'storage' => [
                'audio' => ['total' => (int) $audio->total, 'bytes' => (int) $audio->bytes],
                'images' => ['total' => (int) $images->total, 'bytes' => (int) $images->bytes],
                'others' => ['total' => (int) $otherFiles->total, 'bytes' => (int) $otherFiles->bytes],
                'attachments' => ['total' => (int) $attachmentStats->total, 'bytes' => (int) $attachmentStats->bytes],
                'total_bytes' => $totalBytes,
                'total_files' => $totalFiles,
                'disk_free' => (int) $diskFree,
                'disk_total' => (int) $diskTotal,
                'db_bytes' => $dbBytes,
            ],
            'challenges' => $challenges,
            'totals' => $totals,
        ]);
    }

    /**
     * حذف یک فایل (روز یا ضمیمه) همراه با فایل فیزیکی‌اش.
     */
    public function destroyFile(Request $request, string $type, int $file): RedirectResponse
    {
        abort_unless(in_array($type, ['day-files', 'attachments'], true), 404);

        $model = $type === 'day-files'
            ? DayFile::query()->findOrFail($file)
            : ChallengeAttachment::query()->findOrFail($file);

        if (Storage::disk('local')->exists($model->path)) {
            Storage::disk('local')->delete($model->path);
        }

        $title = $model->title;
        $model->delete();

        return back()->with('status', 'فایل «'.$title.'» حذف شد.');
    }

    /**
     * پاکسازی رکوردهایی که فایلشان روی دیسک نیست (فایل‌های گمشده).
     */
    public function cleanupFiles(): RedirectResponse
    {
        $removed = 0;

        foreach ([DayFile::query()->get(), ChallengeAttachment::query()->get()] as $files) {
            foreach ($files as $file) {
                if (! Storage::disk('local')->exists($file->path)) {
                    $file->delete();
                    $removed++;
                }
            }
        }

        return back()->with(
            'status',
            $removed > 0
                ? fa_num($removed).' رکورد فایل گمشده پاکسازی شد.'
                : 'فایل گمشده‌ای پیدا نشد؛ همه رکوردها مرتب‌اند.'
        );
    }

    /**
     * حجم به واحد خوانا (فارسی).
     */
    public static function humanBytes(int|float|null $bytes): string
    {
        $bytes = max(0, (int) $bytes);
        $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return fa_num(Str::of(round($value, $power === 0 ? 0 : 1))->__toString()).' '.$units[$power];
    }
}
