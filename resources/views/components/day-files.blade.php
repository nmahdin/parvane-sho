{{--
    فایل‌ها و فایل‌های صوتی یک روز.

    - $day:   شماره روز
    - $title: عنوان ریل (پیش‌فرض «فایل‌های این روز»)

    صوتی‌ها با پخش‌کننده اختصاصی سایت پخش می‌شوند و بقیه فایل‌ها با یک دکمه
    دانلود. هر دو داخل کارت همان روز می‌آیند.
--}}
@props(['challenge', 'day' => null, 'title' => null])

@php
    $files = ($day === null || (int) $day < 1) ? collect() : $challenge->filesForDay((int) $day);
    $audios = $files->where('is_audio', true);
    $documents = $files->where('is_audio', false);
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

    @if ($documents->isNotEmpty())
        <div class="rail rail-files">
            <p class="rail-label text-forest">
                <span>📎</span> {{ $title ?? 'فایل‌های این روز' }}
                <span class="font-normal text-muted">{{ fa_num($documents->count()) }} فایل</span>
            </p>

            <ul class="mt-2.5 space-y-2">
                @foreach ($documents as $file)
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
