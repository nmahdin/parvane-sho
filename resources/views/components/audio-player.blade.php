{{--
    پخش‌کننده اختصاصی فایل صوتی — بدون استفاده از پخش‌کننده پیش‌فرض مرورگر.
    ظاهر با پالت سایت هماهنگ است و با JavaScript خود سایت (data-audio-player)
    کار می‌کند: پخش/توقف، نوار پیشرفت لمسی، زمان باقی‌مانده، سرعت پخش و
    «فقط یکی پخش شود».

    - $file: نمونه DayFile
    - $day:  شماره روز (برای شناسه یکتا)
--}}
@props(['file', 'day' => null])

@php
    $playerId = 'audio-player-'.$file->id;
@endphp

<div class="audio-player"
     id="{{ $playerId }}"
     data-audio-player
     data-audio-src="{{ $file->url() }}"
     data-audio-play-url="{{ route('challenge.day-files.plays', [$file->challenge, $file]) }}"
     data-audio-title="{{ $file->title }}">
    <div class="flex items-center gap-3">
        <button type="button"
                class="audio-play"
                data-audio-toggle
                aria-label="پخش فایل صوتی {{ $file->title }}"
                aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="currentColor" class="size-5" data-audio-icon="play" aria-hidden="true">
                <path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l11.14-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14z"/>
            </svg>
            <svg viewBox="0 0 24 24" fill="currentColor" class="hidden size-5" data-audio-icon="pause" aria-hidden="true">
                <path d="M7 4h3.5v16H7zM13.5 4H17v16h-3.5z"/>
            </svg>
        </button>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span class="truncate text-sm font-semibold text-ink">{{ $file->title }}</span>

                <span class="badge shrink-0 bg-gold/15 text-gold-ink ring-gold/40">
                    🎧 صوتی
                    @if ($day)
                        · روز {{ fa_num($day) }}
                    @endif
                </span>
            </div>

            <label class="mt-2 block">
                <span class="sr-only">نوار پیشرفت پخش {{ $file->title }}</span>
                <input type="range"
                       class="audio-track"
                       min="0"
                       max="1000"
                       value="0"
                       step="1"
                       data-audio-seek
                       aria-label="نوار پیشرفت پخش">
            </label>

            <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                <span class="audio-time">
                    <span data-audio-current>۰۰:۰۰</span>
                    <span class="text-hint"> / </span>
                    <span data-audio-duration>۰۰:۰۰</span>
                </span>

                <span class="flex items-center gap-1.5">
                    <span class="text-hint">{{ $file->sizeForHumans() }}</span>

                    <button type="button" class="audio-speed" data-audio-speed aria-label="تغییر سرعت پخش">۱×</button>

                    <a href="{{ $file->url() }}"
                       class="audio-download"
                       download
                       aria-label="دانلود فایل صوتی {{ $file->title }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                            <path d="M12 3v12"/>
                            <path d="M7 11l5 5 5-5"/>
                            <path d="M4 20h16"/>
                        </svg>
                        دانلود صوت
                    </a>
                </span>
            </div>
        </div>
    </div>
</div>
