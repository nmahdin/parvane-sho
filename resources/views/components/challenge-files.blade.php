@props(['challenge', 'title' => 'فایل‌های ضمیمه', 'variant' => 'card'])

@if ($challenge->attachments->isNotEmpty())
    <div {{ $attributes->merge(['class' => $variant === 'plain' ? '' : 'card']) }}>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span>📎</span> {{ $title }}
        </h2>

        <ul class="mt-3 space-y-2">
            @foreach ($challenge->attachments as $attachment)
                <li>
                    <a href="{{ $attachment->url() }}"
                       class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 transition hover:bg-line/50">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-base ring-1 ring-line">{{ $attachment->icon() }}</span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm text-ink">{{ $attachment->title }}</span>
                            <span class="mt-0.5 block text-[11px] text-hint">{{ $attachment->sizeForHumans() }}</span>
                        </span>

                        <span class="shrink-0 text-xs text-brand-700">دانلود</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
