@props(['percent' => 0, 'class' => 'bg-brand-500', 'height' => 'h-2', 'showLabel' => false])

@php($value = max(0, min(100, (int) $percent)))

<div class="flex items-center gap-3">
    <div class="w-full overflow-hidden rounded-full bg-line/60 {{ $height }}">
        <div class="h-full rounded-full transition-all duration-500 {{ $class }}" style="width: {{ $value }}%"></div>
    </div>

    @if ($showLabel)
        <span class="shrink-0 text-xs font-medium text-muted">{{ fa_num($value) }}٪</span>
    @endif
</div>
