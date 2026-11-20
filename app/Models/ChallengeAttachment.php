<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'challenge_id',
    'title',
    'path',
    'original_name',
    'size',
    'mime',
    'downloads',
    'sort_order',
])]
class ChallengeAttachment extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'downloads' => 'integer',
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

    public function url(): string
    {
        return route('challenge.files.show', [$this->challenge, $this]);
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

    public function icon(): string
    {
        $mime = (string) $this->mime;
        $extension = strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return match (true) {
            str_contains($mime, 'pdf') || $extension === 'pdf' => '📕',
            str_contains($mime, 'image') => '🖼️',
            str_contains($mime, 'audio') => '🎧',
            str_contains($mime, 'video') => '🎬',
            str_contains($mime, 'zip') || in_array($extension, ['rar', '7z'], true) => '🗜️',
            in_array($extension, ['doc', 'docx'], true) => '📄',
            in_array($extension, ['xls', 'xlsx', 'csv'], true) => '📊',
            in_array($extension, ['ppt', 'pptx'], true) => '📽️',
            default => '📎',
        };
    }
}
