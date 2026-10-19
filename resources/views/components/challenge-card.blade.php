@props(['challenge'])

@php($style = $challenge->style())

<a href="{{ route('challenges.show', $challenge) }}"
   class="group flex h-full flex-col gap-5 rounded-3xl bg-paper p-6 surface transition duration-300 hover:-translate-y-1 hover:shadow-lg">
    <div class="flex items-start gap-3">
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-tl {{ $style['gradient'] }} text-xl text-white shadow-sm">
            <x-challenge-icon :value="$challenge->emoji" size="size-6" />
        </span>

        <div class="min-w-0">
            <h3 class="truncate text-base font-semibold text-ink group-hover:text-brand-700">{{ $challenge->title }}</h3>
            <p class="mt-1 line-clamp-2 text-xs leading-6 text-muted">{{ $challenge->tagline ?: 'بدون توضیح کوتاه' }}</p>
        </div>
    </div>

    <div class="mt-auto space-y-3">
        <x-progress-bar :percent="$challenge->progressPercent()" :class="$style['bar']" />

        <div class="flex items-center justify-between text-xs text-muted">
            <span>{{ fa_num($challenge->participants_count) }} شرکت‌کننده</span>
            <span>
                {{ fa_num($challenge->tasksCount()) }} کار
                @if ($challenge->questionsCount() > 0)
                    · {{ fa_num($challenge->questionsCount()) }} پرسش
                @endif
            </span>
            <span>{{ fa_num($challenge->totalDays()) }} روز</span>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line pt-3">
            <span class="badge {{ $challenge->statusClasses() }}">{{ $challenge->statusLabel() }}</span>

            <span class="text-xs text-muted">
                @if ($challenge->currentDayNumber())
                    امروز روز {{ fa_num($challenge->currentDayNumber()) }} از {{ fa_num($challenge->totalDays()) }}
                @else
                    {{ $challenge->dateRangeLabel() }}
                @endif
            </span>
        </div>
    </div>
</a>
