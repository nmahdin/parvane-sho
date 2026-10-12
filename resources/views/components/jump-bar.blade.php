{{--
    نوار پرش سریع: دسترسی یک‌کلیکی به روز جاری، روزها و بخش‌های صفحه
    - $openDays: شماره روزهای باز برای چیپ‌ها
    - $today: شماره روز جاری (چیپ فعال)
    - $lockedDays: اگر قفل داشته باشیم، شماره‌هایشان برای چیپ «روزهای قفل»
    - $anchors: [['href' => '#files', 'label' => 'فایل‌ها'], ...]
--}}
@props([
    'openDays' => [],
    'today' => null,
    'lockedDays' => [],
    'anchors' => [],
])

<nav class="jump-bar no-print" aria-label="پرش سریع">
    @if ($today)
        <a href="#day-{{ $today }}" data-jump-day="day-{{ $today }}" class="jump-chip is-active">
            <span>⭐</span> امروز (روز {{ fa_num($today) }})
        </a>
    @endif

    @foreach ($openDays as $day)
        @if ((int) $day !== (int) $today)
            <a href="#day-{{ $day }}" data-jump-day="day-{{ $day }}" class="jump-chip">
                {{ (int) $day > 0 ? 'روز '.fa_num($day) : '★ کارهای کلی' }}
            </a>
        @endif
    @endforeach

    @if (count($lockedDays))
        <a href="#locked-days" class="jump-chip is-locked">
            <span>🔒</span>
            @if (count($lockedDays) === 1)
                روز {{ fa_num(min($lockedDays)) }}
            @else
                روزهای {{ fa_num(min($lockedDays)) }} تا {{ fa_num(max($lockedDays)) }}
            @endif
        </a>
    @endif

    @foreach ($anchors as $anchor)
        <a href="{{ $anchor['href'] }}" @isset($anchor['any']) data-jump-any="{{ $anchor['any'] }}" @endisset class="jump-chip">{{ $anchor['label'] }}</a>
    @endforeach
</nav>
