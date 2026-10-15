{{--
    فایل‌ها و فایل‌های صوتی یک روز.

    - $day:   شماره روز
    - $title: عنوان ریل (پیش‌فرض «فایل‌های این روز»)

    صوتی‌ها با پخش‌کننده اختصاصی سایت پخش می‌شوند، عکس‌ها کامل و به اندازه
    عرض باکس روز نمایش داده می‌شوند (کلیک = اندازه اصلی) و بقیه فایل‌ها با
    یک دکمه دانلود. همه داخل کارت همان روز می‌آیند.
--}}
@props(['challenge', 'day' => null, 'title' => null])

@php
    $files = ($day === null || (int) $day < 1) ? collect() : $challenge->filesForDay((int) $day);
    $audios = $files->where('is_audio', true);
    $documents = $files->where('is_audio', false);
    $images = $documents->filter(fn ($file) => $file->isImage());
    $others = $documents->reject(fn ($file) => $file->isImage());
@endphp

@if ($files->isNotEmpty())
    @if ($audios->isNotEmpty())
        <div class="rail rail-audio">
            <p class="rail-label text-gold-ink">
                <span>🎧</span> فایل‌های صوتی این روز
                <span class="font-normal text-muted">{{ fa_num($audios->count()) }} صوت — همین‌جا پخش می‌شود</span>
            </p>

            <div class="mt-2.5 space-y-2">
                @foreach ($audios as $audio)
                    <x-audio-player :file="$audio" :day="(int) $day" />
                @endforeach
            </div>
        </div>
    @endif

    @if ($images->isNotEmpty())
        <div class="rail rail-images">
            <p class="rail-label text-forest">
                <span>🖼</span> تصاویر این روز
                <span class="font-normal text-muted">{{ fa_num($images->count()) }} تصویر — تمام عرض باکس روز</span>
            </p>

            <div class="mt-2.5 space-y-3">
                @foreach ($images as $image)
                    <figure>
                        <a href="{{ $image->url() }}"
                           class="block"
                           target="_blank"
                           rel="noopener"
                           aria-label="نمایش {{ $image->title }} در اندازه اصلی">
                            <img src="{{ $image->url() }}"
                                 alt="{{ $image->title }}"
                                 loading="lazy"
                                 decoding="async"
                                 class="day-image">
                        </a>

                        <figcaption class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <span class="min-w-0 truncate text-[11px] text-hint">{{ $image->title }} · {{ $image->sizeForHumans() }}</span>

                            <a href="{{ $image->url() }}"
                               class="day-image-download"
                               download
                               aria-label="دانلود تصویر {{ $image->title }}">⬇ دانلود</a>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    @endif

    @if ($others->isNotEmpty())
        <div class="rail rail-files">
            <p class="rail-label text-forest">
                <span>📎</span> {{ $title ?? 'فایل‌های این روز' }}
                <span class="font-normal text-muted">{{ fa_num($others->count()) }} فایل</span>
            </p>

            <ul class="mt-2.5 space-y-2">
                @foreach ($others as $file)
                    <li>
                        <a href="{{ $file->url() }}"
                           class="flex items-center gap-3 rounded-2xl bg-paper px-3.5 py-3 transition hover:bg-cream"
                           download>
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-cream text-base ring-1 ring-line">{{ $file->icon() }}</span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink">{{ $file->title }}</span>
                                <span class="mt-0.5 block text-[11px] text-hint">
                                    {{ $file->extension() }} · {{ $file->sizeForHumans() }}
                                </span>
                            </span>

                            <span class="shrink-0 rounded-lg bg-jade/10 px-2.5 py-1.5 text-[11px] font-medium text-jade">⬇ دانلود</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
