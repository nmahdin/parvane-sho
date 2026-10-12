@props(['challenge', 'day' => null, 'title' => null])

@php
    // فقط روزهای شمارهدار؛ نکته‌های کلی در بخش «نکته‌های کلی چالش» می‌آیند
    $tips = ($day === null || (int) $day < 1) ? collect() : $challenge->tipsForDay((int) $day);
@endphp

@if ($tips->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'border-t border-line/60 bg-gold/5 px-5 py-4']) }}>
        <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-forest">
            <span>💡</span> {{ $title ?? 'نکته‌های این روز' }}
            <span class="font-normal text-muted">{{ fa_num($tips->count()) }} نکته</span>
        </p>

        <ul class="mt-3 space-y-2">
            @foreach ($tips as $tip)
                <li class="rounded-2xl bg-gradient-to-tl from-gold/15 to-paper p-3 ring-1 ring-gold/50">
                    <div class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-base shadow-sm ring-1 ring-gold/50">{{ $tip->emoji }}</span>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-forest">{{ $tip->title }}</p>
                            @if ($tip->body)
                                <p class="mt-1.5 text-xs leading-6 text-ink/80">{{ $tip->body }}</p>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
