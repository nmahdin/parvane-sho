<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * فایل یا فایل صوتیِ یک روز مشخص از چالش.
 *
 * بر خلاف «فایل‌های ضمیمه چالش» که به کل چالش تعلق دارند، این فایل‌ها با
 * `day_number` به یک روز وصل‌اند و داخل کارت همان روز نمایش داده می‌شوند؛
 * فایل‌های صوتی (`is_audio`) در سایت با پخش‌کننده اختصاصی پخش می‌شوند.
 */
#[Fillable([
    'challenge_id',
    'day_number',
    'title',
    'path',
    'original_name',
    'size',
    'mime',
    'is_audio',
    'is_video',
    'downloads',
    'plays',
    'sort_order',
])]
class DayFile extends Model
{
    /**
     * پسوندهایی که به‌عنوان فایل صوتی شناخته می‌شوند.
     *
     * @var list<string>
     */
    public const AUDIO_EXTENSIONS = ['mp3', 'm4a', 'aac', 'ogg', 'oga', 'wav', 'flac', 'opus'];

    /**
     * پسوندهایی که به‌عنوان تصویر شناخته می‌شوند.
     *
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp'];

    /**
     * پسوندهایی که به‌عنوان ویدیو شناخته می‌شوند.
     *
     * @var list<string>
     */
    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogv', 'mov', 'm4v', '3gp'];

    /**
     * بیشترین حجم مجاز هر فایل (کیلوبایت) — ۶۰ مگابایت برای همهٔ
     * فایل‌ها (صوت، ویدیو، تصویر، pdf و ...).
     */
    public const MAX_KILOBYTES = 61440;

    /**
     * سقف حجم فایل صوتی — همان ۶۰ مگابایت (این ثابت برای سازگاری با
     * کدهای قبلی نگه داشته شده است).
     */
    public const MAX_AUDIO_KILOBYTES = self::MAX_KILOBYTES;

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'size' => 'integer',
            'is_audio' => 'boolean',
            'is_video' => 'boolean',
            'downloads' => 'integer',
            'plays' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * این فایل صوتی است؟ (هم از روی نوع MIME و هم پسوند نام فایل)
     */
    public static function looksLikeAudio(?string $mime, ?string $filename): bool
    {
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        return str_contains((string) $mime, 'audio')
            || in_array($extension, self::AUDIO_EXTENSIONS, true);
    }

    /**
     * این فایل ویدیو است؟ (هم از روی نوع MIME و هم پسوند نام فایل)
     */
    public static function looksLikeVideo(?string $mime, ?string $filename): bool
    {
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        return str_starts_with((string) $mime, 'video/')
            || in_array($extension, self::VIDEO_EXTENSIONS, true);
    }

    /**
     * این فایل ویدیو است؟ (برای پخش با پخش‌کنندهٔ اختصاصی سایت)
     */
    public function isVideo(): bool
    {
        return ! $this->is_audio && static::looksLikeVideo($this->mime, $this->original_name);
    }

    /**
     * این فایل تصویر است؟ (برای نمایش کامل عکس در سایت)
     */
    public function isImage(): bool
    {
        if ($this->is_audio) {
            return false;
        }

        $extension = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return str_starts_with((string) $this->mime, 'image/')
            || in_array($extension, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * نشانی دانلود (یا پخش) فایل.
     */
    public function url(): string
    {
        return route('challenge.day-files.show', [$this->challenge, $this]);
    }

    public function exists(): bool
    {
        return Storage::disk('local')->exists($this->path);
    }

    public function sizeForHumans(): string
    {
        $bytes = max(0, (int) $this->size);
        $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return fa_num(round($value, $power === 0 ? 0 : 1)).' '.$units[$power];
    }

    public function extension(): string
    {
        return strtoupper(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));
    }

    public function icon(): string
    {
        if ($this->is_audio) {
            return '🎧';
        }

        if ($this->is_video || $this->isVideo()) {
            return '🎬';
        }

        $mime = (string) $this->mime;
        $extension = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return match (true) {
            str_contains($mime, 'pdf') || $extension === 'pdf' => '📕',
            str_contains($mime, 'image') => '🖼️',
            str_contains($mime, 'video') => '🎬',
            str_contains($mime, 'zip') || in_array($extension, ['rar', '7z'], true) => '🗜️',
            in_array($extension, ['doc', 'docx'], true) => '📄',
            in_array($extension, ['xls', 'xlsx', 'csv'], true) => '📊',
            in_array($extension, ['ppt', 'pptx'], true) => '📽️',
            default => '📎',
        };
    }

    /**
     * برچسب نوع فایل برای نمایش در پنل و سایت.
     */
    public function typeLabel(): string
    {
        if ($this->is_audio) {
            return 'فایل صوتی';
        }

        // ویدیوهای قدیمی (پیش از ستون is_video) هم از روی نام/نوع شناخته می‌شوند
        return ($this->is_video || $this->isVideo()) ? 'ویدیو' : 'فایل';
    }
}
