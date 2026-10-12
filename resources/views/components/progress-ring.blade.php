@props([
    'percent' => 0,
    'size' => 140,
    'strokeWidth' => 12,
    'class' => 'stroke-brand-500',
    'caption' => null,
])

@php
    $value = max(0, min(100, (int) $percent));
    $radius = ($size - $strokeWidth) / 2;
    $circumference = 2 * M_PI * $radius;
    $dash = $circumference * $value / 100;
@endphp

<div class="flex flex-col items-center">
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px">
        <svg viewBox="0 0 {{ $size }} {{ $size }}" class="size-full -rotate-90" role="img" aria-label="{{ fa_num($value) }} درصد">
            <circle
                cx="{{ $size / 2 }}"
                cy="{{ $size / 2 }}"
                r="{{ $radius }}"
                fill="none"
                class="stroke-line"
                stroke-width="{{ $strokeWidth }}"
            />
            <circle
                cx="{{ $size / 2 }}"
                cy="{{ $size / 2 }}"
                r="{{ $radius }}"
                fill="none"
                class="{{ $class }}"
                stroke-width="{{ $strokeWidth }}"
                stroke-linecap="round"
                stroke-dasharray="{{ $dash }} {{ $circumference }}"
            />
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-2xl font-bold text-ink">{{ fa_num($value) }}<span class="text-sm font-medium text-muted">٪</span></span>
            @if ($caption)
                <span class="mt-0.5 text-[11px] text-muted">{{ $caption }}</span>
            @endif
        </div>
    </div>
</div>
