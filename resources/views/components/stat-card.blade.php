@props(['label', 'value', 'hint' => null, 'icon' => '✨', 'tone' => 'brand'])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'emerald' => 'bg-leaf/10 text-forest',
        'amber' => 'bg-gold/15 text-ink',
        'sky' => 'bg-jade/10 text-jade',
    ];
@endphp

<div class="card flex items-center gap-4 p-5">
    <span class="grid size-11 shrink-0 place-items-center rounded-2xl text-lg {{ $tones[$tone] ?? $tones['brand'] }}">{{ $icon }}</span>

    <div class="min-w-0">
        <p class="text-xs text-muted">{{ $label }}</p>
        <p class="mt-0.5 text-xl font-bold text-ink">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 truncate text-[11px] text-muted/80">{{ $hint }}</p>
        @endif
    </div>
</div>
