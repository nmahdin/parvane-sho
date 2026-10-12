@props(['label', 'value', 'hint' => null, 'icon' => '✨', 'tone' => 'brand'])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'sky' => 'bg-sky-50 text-sky-700',
    ];
@endphp

<div class="card flex items-center gap-4 p-5">
    <span class="grid size-11 shrink-0 place-items-center rounded-2xl text-lg {{ $tones[$tone] ?? $tones['brand'] }}">{{ $icon }}</span>

    <div class="min-w-0">
        <p class="text-xs text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-xl font-bold text-slate-900">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $hint }}</p>
        @endif
    </div>
</div>
