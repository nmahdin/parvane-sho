{{--
    کارت یک روز به‌صورت آکاردئون (details/summary)
    - $day: شماره روز (۰ برای «بدون روز مشخص»)
    - $open: پیش‌فرض باز باشد؟ (روز جاری باز است)
    - $status: متن خلاصه سمت چپ ردیف (مثل «۳ از ۵ مورد» یا «۲ پرسش · ۳ کار»)
--}}
@props([
    'challenge',
    'day',
    'today' => false,
    'open' => false,
    'status' => null,
    'statusClass' => 'text-hint',
])

@php
    $number = (int) $day;
    $title = $number > 0 ? $challenge->dayTitle($number) : null;
    $dateLabel = $number > 0 ? $challenge->dayDateLabel($number) : null;
@endphp

<details id="day-{{ $day }}" @if ($open) open @endif class="day-card {{ $today ? 'is-today' : '' }}"
         data-day-view-url="{{ route('challenge.day-views.store', $challenge) }}"
         data-day-view-number="{{ $number }}"
         data-day-view-open="{{ $open ? '1' : '' }}">
    <summary>
        <span class="day-num {{ $today ? 'bg-jade text-white' : 'bg-gold/60 text-forest' }}"
              aria-hidden="true">{{ $number > 0 ? fa_num($number) : '★' }}</span>

        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink">
                {{ $number > 0 ? 'روز '.fa_num($number) : 'کارهای کلی' }}

                @if ($title)
                    <span class="text-xs font-normal text-muted">— {{ $title }}</span>
                @endif

                @if ($today)
                    <span class="badge bg-jade/10 text-jade ring-jade/30">امروز</span>
                @endif
            </span>

            @if ($dateLabel)
                <span class="mt-0.5 block text-[11px] text-hint">{{ $dateLabel }}</span>
            @endif
        </span>

        @if ($status)
            <span class="hidden shrink-0 text-[11px] {{ $statusClass }} sm:inline">{{ $status }}</span>
        @endif

        <span class="day-chev" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </summary>

    <div class="day-body">
        {{ $slot }}
    </div>
</details>
