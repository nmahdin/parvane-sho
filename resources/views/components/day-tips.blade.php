@props(['challenge', 'day' => null, 'title' => null, 'divider' => true])

@php
    // فقط روزهای شماره‌دار؛ نکته‌های کلی در بخش «نکته‌های کلی چالش» می‌آیند
    $tips = ($day === null || (int) $day < 1) ? collect() : $challenge->tipsForDay((int) $day);
@endphp

@if ($tips->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'rail rail-tips']) }}>
        <p class="rail-label text-gold-ink">
            <span>💡</span> {{ $title ?? 'نکته‌های این روز' }}
            <span class="font-normal text-muted">{{ fa_num($tips->count()) }} نکته</span>
        </p>

        <ul class="mt-2 space-y-2">
            @foreach ($tips as $tip)
                <li class="flex items-start gap-2.5">
                    <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-paper text-sm ring-1 ring-gold/40">{{ $tip->emoji }}</span>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-forest">{{ $tip->title }}</p>

                        @if ($tip->body)
                            <p class="mt-1 text-xs leading-6 text-ink/80">{{ $tip->body }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
