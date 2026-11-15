{{--
    پخش‌کننده اختصاصی ویدیو — بدون استفاده از کنترل‌های پیش‌فرض مرورگر.
    ظاهر با پالت سایت هماهنگ است و با JavaScript خود سایت (data-video-player)
    کار می‌کند: پخش/توقف، نوار پیشرفت لمسی، زمان، سرعت پخش، بی‌صدا،
    تمام‌صفحه، «فقط یک رسانه در حال پخش» و ادامه از جای قبلی.

    - $file: نمونه DayFile
    - $day:  شماره روز (برای شناسه یکتا)
--}}
@props(['file', 'day' => null])

@php
    $playerId = 'video-player-'.$file->id;
@endphp

<div class="video-player"
     id="{{ $playerId }}"
     data-video-player
     data-video-id="{{ $file->id }}"
     data-video-src="{{ $file->url() }}"
     data-video-play-url="{{ route('challenge.day-files.plays', [$file->challenge, $file]) }}"
     data-video-title="{{ $file->title }}">
    <div class="video-screen">
        {{-- تا پیش از اولین پخش هیچ بایتی از ویدیو دانلود نمی‌شود (preload=none) --}}
        <video class="video-stage"
               preload="none"
               playsinline
               data-video-stage
               aria-label="ویدیوی {{ $file->title }}"></video>

        <button type="button"
                class="video-bigplay"
                data-video-toggle
                aria-label="پخش ویدیوی {{ $file->title }}"
                aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="currentColor" class="size-8" data-video-icon="play" aria-hidden="true">
                <path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l11.14-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14z"/>
            </svg>
            <svg viewBox="0 0 24 24" fill="currentColor" class="hidden size-8" data-video-icon="pause" aria-hidden="true">
                <path d="M7 4h3.5v16H7zM13.5 4H17v16h-3.5z"/>
            </svg>
        </button>

        <span class="video-buffering hidden" data-video-buffering aria-hidden="true"></span>
    </div>

    <div class="video-bar">
        <button type="button"
                class="video-ctl"
                data-video-toggle
                aria-label="پخش ویدیوی {{ $file->title }}"
                aria-pressed="false"
                tabindex="-1">
            <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" data-video-icon="play" aria-hidden="true">
                <path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l11.14-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14z"/>
            </svg>
            <svg viewBox="0 0 24 24" fill="currentColor" class="hidden size-4" data-video-icon="pause" aria-hidden="true">
                <path d="M7 4h3.5v16H7zM13.5 4H17v16h-3.5z"/>
            </svg>
        </button>

        <label class="video-seek-wrap">
            <span class="sr-only">نوار پیشرفت ویدیوی {{ $file->title }}</span>
            <input type="range"
                   class="video-track"
                   min="0"
                   max="1000"
                   value="0"
                   step="1"
                   data-video-seek
                   aria-label="نوار پیشرفت ویدیو">
        </label>

        <span class="video-time">
            <span data-video-current>۰۰:۰۰</span>
            <span class="text-hint"> / </span>
            <span data-video-duration>۰۰:۰۰</span>
        </span>

        <button type="button" class="video-speed" data-video-speed aria-label="تغییر سرعت پخش ویدیو">۱×</button>

        <button type="button" class="video-ctl" data-video-mute aria-label="بی‌صدا کردن ویدیو" aria-pressed="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" data-video-icon="sound-on" aria-hidden="true">
                <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                <path d="M17 8.5a5 5 0 0 1 0 7"/>
            </svg>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden size-4" data-video-icon="sound-off" aria-hidden="true">
                <path d="M4 9v6h4l5 4V5L8 9H4z"/>
                <path d="M17 9.5l4 5M21 9.5l-4 5"/>
            </svg>
        </button>

        <button type="button" class="video-ctl" data-video-fullscreen aria-label="تمام‌صفحه کردن ویدیو">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                <path d="M4 9V4h5M20 15v5h-5M20 9V4h-5M4 15v5h5"/>
            </svg>
        </button>

        <a href="{{ $file->url() }}" class="video-ctl" download aria-label="دانلود ویدیوی {{ $file->title }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                <path d="M12 3v12"/>
                <path d="M7 11l5 5 5-5"/>
                <path d="M4 20h16"/>
            </svg>
        </a>
    </div>

    <p class="video-meta">
        <span class="badge bg-forest/15 text-forest ring-forest/40">
            🎬 ویدیو
            @if ($day)
                · روز {{ fa_num($day) }}
            @endif
        </span>

        <span class="truncate">{{ $file->title }}</span>
        <span class="text-hint">{{ $file->extension() }} · {{ $file->sizeForHumans() }}</span>
    </p>
</div>
