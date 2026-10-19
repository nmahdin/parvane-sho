@props(['value', 'size' => 'size-6'])

@php
    /* آیکون‌های اختصاصی چالش: کلید متن کوتاه → SVG خطی.
       رنگ از context می‌آید (currentColor) تا روی پس‌زمینهٔ رنگی سفید
       یا روی کاشی کرم، سبز دیده شود. هر مقدار دیگری = همان ایموجی متنی. */
    $challengeIconPaths = [
        'star' => '<path d="M12 3.6l2.6 5.2 5.8.9-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.2-4.1 5.8-.9z"/>',
        'heart' => '<path d="M12 20.4s-7.4-4.6-7.4-9.9A4.4 4.4 0 0 1 12 7.4a4.4 4.4 0 0 1 7.4 3.1c0 5.3-7.4 9.9-7.4 9.9z"/>',
        'leaf' => '<path d="M5.5 18.5c8.5 1 13.5-4 13.5-13.5-9 0-14.5 4.5-13.5 13.5z"/><path d="M6.5 17.5c2-5.5 5-8.8 9.5-10.5"/>',
        'moon' => '<path d="M20.4 14.9A8.5 8.5 0 1 1 9.1 3.6a7 7 0 0 0 11.3 11.3z"/>',
        'sun' => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.9v2.2M12 18.9v2.2M2.9 12h2.2M18.9 12h2.2M5.6 5.6l1.5 1.5M16.9 16.9l1.5 1.5M18.4 5.6l-1.5 1.5M7.1 16.9l-1.5 1.5"/>',
        'drop' => '<path d="M12 3.6s6 6.4 6 10.2a6 6 0 0 1-12 0c0-3.8 6-10.2 6-10.2z"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.4"/><circle cx="12" cy="12" r="1.2"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V4H6.5A2.5 2.5 0 0 0 4 6.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
    ];

    $challengeIconKey = is_string($value) ? strtolower(trim($value)) : '';
@endphp

@if (isset($challengeIconPaths[$challengeIconKey]))
    <svg {{ $attributes->merge(['class' => "$size inline-block align-middle"]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $challengeIconPaths[$challengeIconKey] !!}</svg>
@else
    <span {{ $attributes }}>{{ $value }}</span>
@endif
