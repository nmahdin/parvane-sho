@extends('layouts.public')

@php
    $style = $challenge->style();
    $questions = $challenge->questions->where('is_published', true);
    $attachments = $challenge->attachments;
    $tips = $challenge->tips;
@endphp

@section('title', $challenge->title.' — '.($site['site_name'] ?? config('panel.site_name')))
@section('description', $challenge->tagline ?: $challenge->title)

@section('content')
    <section class="mx-auto max-w-6xl px-4 pt-12 sm:px-6">
        <nav class="no-print text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-brand-700">خانه</a>
            <span class="mx-2">/</span>
            <a href="{{ route('challenges.index') }}" class="hover:text-brand-700">چالش‌ها</a>
            <span class="mx-2">/</span>
            <span class="text-ink">{{ $challenge->title }}</span>
        </nav>

        <div class="mt-6 overflow-hidden rounded-[2rem] bg-paper shadow-sm ring-1 ring-line">
            <div class="bg-gradient-to-tl {{ $style['gradient'] }} px-6 py-10 text-white sm:px-10">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="max-w-2xl">
                        <span class="grid size-14 place-items-center rounded-3xl bg-white/15 text-2xl backdrop-blur">{{ $challenge->emoji }}</span>
                        <h1 class="mt-5 text-3xl text-white sm:text-4xl">{{ $challenge->title }}</h1>

                        @if ($challenge->tagline)
                            <p class="mt-4 text-sm leading-8 text-white/85">{{ $challenge->tagline }}</p>
                        @endif
                    </div>

                    <span class="badge bg-white/15 text-white ring-white/25">{{ $challenge->statusLabel() }}</span>
                </div>

                <dl class="mt-8 grid grid-cols-2 gap-5 border-t border-white/20 pt-6 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-white/70">شروع</dt>
                        <dd class="mt-1 font-medium">{{ $challenge->starts_on ? fa_date($challenge->starts_on) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-white/70">پایان</dt>
                        <dd class="mt-1 font-medium">{{ $challenge->ends_on ? fa_date($challenge->ends_on) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-white/70">تعداد روزها</dt>
                        <dd class="mt-1 font-medium">{{ fa_num($challenge->daysCount()) }} روز</dd>
                    </div>
                    <div>
                        <dt class="text-white/70">شرکت‌کننده‌ها</dt>
                        <dd class="mt-1 font-medium">{{ fa_num($challenge->participants_count) }} نفر</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-8">
            @if ($challenge->description || $challenge->long_description)
                <div class="card">
                    <h2 class="text-lg">درباره این چالش</h2>

                    @if ($challenge->description)
                        <p class="mt-4 text-sm leading-8 text-ink">{{ $challenge->description }}</p>
                    @endif

                    @if ($challenge->long_description)
                        <div class="mt-4 border-t border-line pt-2 text-sm leading-8 text-ink/80">
                            {!! $challenge->longDescriptionHtml() !!}
                        </div>
                    @endif

                    @if ($challenge->contact_note)
                        <div class="mt-6 flex items-start gap-3 rounded-2xl {{ $style['bg'] }} px-4 py-3 text-xs leading-6 {{ $style['text'] }}">
                            <span>📞</span>
                            <span>{{ $challenge->contact_note }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- نکات چالش: نمایش ویژه --}}
            @if ($tips->isNotEmpty())
                <div>
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid size-9 place-items-center rounded-2xl bg-gold/20 text-base">💡</span>
                        <h2 class="text-lg">نکته‌های این چالش</h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($tips as $tip)
                            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-tl from-gold/15 to-paper p-5 ring-1 ring-gold/50">
                                <div class="pointer-events-none absolute -end-6 -top-6 size-20 rounded-full bg-gold/20 blur-2xl"></div>

                                <div class="relative flex items-start gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-paper text-lg shadow-sm ring-1 ring-gold/50">{{ $tip->emoji }}</span>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-forest">{{ $tip->title }}</p>

                                        @if ($tip->body)
                                            <p class="mt-2 text-xs leading-6 text-ink/80">{{ $tip->body }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- روزها و کارها --}}
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg">روزها و کارها</h2>
                    <span class="text-xs text-muted">{{ fa_num($challenge->tasks_count) }} کار</span>
                </div>

                @forelse ($challenge->tasksByDay() as $day => $tasks)
                    @php
                        $dayNumber = $day > 0 ? (int) $day : null;
                        $locked = ! $challenge->isDayUnlocked($dayNumber);
                        $unlockLabel = $challenge->unlockLabel($dayNumber);
                        $isToday = $dayNumber !== null && $challenge->currentDayNumber() === $dayNumber;
                    @endphp

                    <div class="overflow-hidden rounded-3xl bg-paper shadow-sm ring-1 {{ $isToday ? 'ring-2 ring-jade' : 'ring-line' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl text-xs font-bold {{ $locked ? 'bg-line/60 text-muted' : $style['bg'].' '.$style['text'] }}">
                                    {{ $locked ? '🔒' : fa_num($day > 0 ? $day : '★') }}
                                </span>

                                <div>
                                    <h3 class="flex items-center gap-2 text-sm font-semibold text-ink">
                                        {{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}

                                        @if ($isToday)
                                            <span class="badge bg-jade/10 text-jade ring-jade/30">امروز</span>
                                        @elseif ($locked)
                                            <span class="badge bg-gold/15 text-ink ring-gold/50">قفل</span>
                                        @endif
                                    </h3>

                                    @if ($locked)
                                        <p class="mt-1 text-[11px] text-muted">
                                            {{ $unlockLabel ? $unlockLabel.' باز می‌شود' : 'به‌زودی باز می‌شود' }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <span class="text-xs text-muted">
                                @if ($locked)
                                    {{ fa_num($tasks->count()) }} کار (قفل)
                                @else
                                    {{ fa_num($tasks->count()) }} کار
                                @endif
                            </span>
                        </div>

                        @if ($locked)
                            <div class="locked-panel m-4">
                                <p class="text-2xl">🔒</p>
                                <p class="mt-2 text-sm font-medium text-forest">کارهای این روز هنوز آزاد نشده است</p>
                                <p class="mt-1 text-xs leading-6 text-muted">
                                    روزهای چالش یکی‌یکی باز می‌شوند؛ برای دیدن کارهای این روز
                                    @if ($unlockLabel)
                                        تا {{ $unlockLabel }} صبر کن.
                                    @else
                                        کمی صبر کن.
                                    @endif
                                </p>
                            </div>
                        @else
                        <ul class="divide-y divide-line">
                            @foreach ($tasks as $task)
                                <li class="px-6 py-4">
                                    <p class="text-sm font-medium text-ink">{{ $task->title }}</p>
                                    @if ($task->description)
                                        <p class="mt-1.5 text-xs leading-6 text-muted">{{ $task->description }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                @empty
                    <div class="card text-center text-sm text-muted">
                        کارهای این چالش هنوز ثبت نشده است.
                    </div>
                @endforelse
            </div>

            {{-- باکس پرسش و پاسخ --}}
            @if ($questions->isNotEmpty())
                <div id="qa">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid size-9 place-items-center rounded-2xl bg-jade/15 text-base">❓</span>
                        <h2 class="text-lg">پرسش و پاسخ</h2>
                    </div>

                    <div class="space-y-3">
                        @foreach ($questions as $question)
                            <details class="group overflow-hidden rounded-3xl bg-paper shadow-sm ring-1 ring-line">
                                <summary class="flex cursor-pointer items-start gap-3 px-5 py-4">
                                    <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-xl bg-jade/10 text-xs font-bold text-jade">
                                        {{ fa_num($loop->iteration) }}
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-ink">{{ $question->question }}</span>

                                        @if ($question->asked_by)
                                            <span class="mt-1 block text-[11px] text-muted/80">پرسیده‌شده توسط {{ $question->asked_by }}</span>
                                        @endif
                                    </span>

                                    <span class="mt-0.5 text-muted/80 transition group-open:rotate-180">⌄</span>
                                </summary>

                                <div class="border-t border-line bg-cream/70 px-5 py-4 text-xs leading-7 text-ink/80">
                                    {!! $question->answer ? \App\Support\RichText::toHtml($question->answer) : 'پاسخ این پرسش هنوز ثبت نشده است.' !!}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
            <div class="card text-center">
                <x-progress-ring :percent="$challenge->progressPercent()" :class="$style['stroke']" caption="پیشرفت کلی" />

                <p class="mt-5 text-xs leading-6 text-muted">
                    درصدی از همه کارها که شرکت‌کننده‌های این چالش تا این لحظه انجام داده‌اند.
                </p>
            </div>

            {{-- فایل‌های ضمیمه --}}
            @if ($attachments->isNotEmpty())
                <div class="card">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                        <span>📎</span> فایل‌های ضمیمه
                    </h2>

                    <ul class="mt-4 space-y-2">
                        @foreach ($attachments as $attachment)
                            <li>
                                <a href="{{ $attachment->url() }}"
                                   class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 transition hover:bg-line/60">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-base ring-1 ring-line">{{ $attachment->icon() }}</span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm text-ink">{{ $attachment->title }}</span>
                                        <span class="mt-0.5 block text-[11px] text-muted/80">{{ $attachment->sizeForHumans() }}</span>
                                    </span>

                                    <span class="shrink-0 text-xs text-brand-700">دانلود</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <h2 class="text-sm font-semibold text-ink">چطور در این چالش شرکت کنم؟</h2>
                <ol class="mt-4 space-y-4 text-xs leading-6 text-ink/80">
                    <li class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg {{ $style['bg'] }} {{ $style['text'] }} text-[11px] font-bold">۱</span>
                        <span>نامت را به مسئول چالش بگو تا در فهرست شرکت‌کننده‌ها ثبت شود.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg {{ $style['bg'] }} {{ $style['text'] }} text-[11px] font-bold">۲</span>
                        <span>یک کد و لینک کوتاه اختصاصی برایت ساخته می‌شود؛ همان را ذخیره کن.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg {{ $style['bg'] }} {{ $style['text'] }} text-[11px] font-bold">۳</span>
                        <span>با آن کد وارد داشبورد خودت می‌شوی، بدون رمز و ثبت‌نام، و کارهای هر روز را تیک می‌زنی. روزهای بعدی تا تاریخ خودشان قفل می‌مانند.</span>
                    </li>
                </ol>

                <a href="{{ route('participant.lookup') }}" class="btn btn-soft mt-5 w-full">ورود با کد اختصاصی</a>
            </div>
        </aside>
    </section>
@endsection
