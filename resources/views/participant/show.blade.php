@extends('layouts.public')

@php
    $style = $challenge->style();
    $progress = $participant->progressPercent();
    $completed = $participant->completedCount();
    $total = $participant->totalTasks();
    $initial = mb_substr(trim($participant->name), 0, 1);
@endphp

@section('title', 'داشبورد '.$participant->name.' — '.$challenge->title)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="mx-auto max-w-5xl px-4 pt-12 sm:px-6">
        <x-flash />

        {{-- سرصفحه اختصاصی --}}
        <div class="mt-4 overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-900/5">
            <div class="flex flex-wrap items-center justify-between gap-6 bg-gradient-to-tl {{ $style['gradient'] }} px-6 py-8 text-white sm:px-10">
                <div class="flex items-center gap-4">
                    <span class="grid size-14 place-items-center rounded-3xl bg-white/15 text-xl font-bold backdrop-blur">{{ $initial }}</span>
                    <div>
                        <p class="text-xs text-white/75">داشبورد اختصاصی چالش</p>
                        <h1 class="mt-1 text-2xl text-white">سلام {{ $participant->name }} 👋</h1>
                        <p class="mt-1.5 text-xs text-white/80">{{ $challenge->emoji }} {{ $challenge->title }}</p>
                    </div>
                </div>

                <div class="text-left">
                    <span class="badge bg-white/15 text-white ring-white/25">{{ $challenge->statusLabel() }}</span>
                    <p class="mt-3 text-xs text-white/80">{{ $challenge->dateRangeLabel() }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            {{-- ستون کارها --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg">کارهای من</h2>
                            <p class="mt-1.5 text-xs text-slate-500">
                                {{ fa_num($completed) }} از {{ fa_num($total) }} کار انجام شده است.
                                @if ($challenge->currentDayNumber())
                                    امروز روز {{ fa_num($challenge->currentDayNumber()) }} این چالش است.
                                @endif
                            </p>
                        </div>

                        <span class="badge bg-slate-50 text-slate-600 ring-slate-200">
                            🔥 {{ fa_num($participant->currentStreak()) }} روز پیوسته
                        </span>
                    </div>

                    <div class="mt-5">
                        <x-progress-bar :percent="$progress" :class="$style['bar']" height="h-3" show-label />
                    </div>
                </div>

                @forelse ($challenge->tasksByDay() as $day => $tasks)
                    @php
                        $doneInDay = $tasks->filter(fn ($task) => $participant->isTaskDone($task))->count();
                        $dayComplete = $doneInDay === $tasks->count();
                    @endphp

                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-900/5">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl {{ $dayComplete ? 'bg-emerald-500 text-white' : $style['bg'].' '.$style['text'] }} text-xs font-bold">
                                    {{ $dayComplete ? '✓' : fa_num($day > 0 ? $day : '★') }}
                                </span>
                                <h3 class="text-sm font-semibold text-slate-900">
                                    {{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}
                                </h3>
                            </div>

                            <span class="text-xs {{ $dayComplete ? 'text-emerald-600' : 'text-slate-500' }}">
                                {{ fa_num($doneInDay) }} از {{ fa_num($tasks->count()) }}
                            </span>
                        </div>

                        <div class="space-y-2 p-3">
                            @foreach ($tasks as $task)
                                @php($done = $participant->isTaskDone($task))

                                <form id="task-{{ $task->id }}" method="POST" action="{{ route('participant.tasks.toggle', [$participant, $task]) }}">
                                    @csrf

                                    <button type="submit"
                                            class="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-right transition {{ $done ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'bg-slate-50 hover:bg-slate-100' }}"
                                            aria-pressed="{{ $done ? 'true' : 'false' }}">
                                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg text-xs font-bold transition {{ $done ? 'bg-emerald-500 text-white' : 'bg-white text-transparent ring-1 ring-slate-300' }}">
                                            ✓
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block text-sm {{ $done ? 'text-slate-400 line-through' : 'font-medium text-slate-800' }}">
                                                {{ $task->title }}
                                            </span>

                                            @if ($task->description)
                                                <span class="mt-1 block text-xs leading-6 {{ $done ? 'text-slate-400' : 'text-slate-500' }}">
                                                    {{ $task->description }}
                                                </span>
                                            @endif
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="card text-center text-sm text-slate-500">
                        هنوز کاری برای این چالش ثبت نشده است. کمی بعد دوباره سر بزن.
                    </div>
                @endforelse

                {{-- نکات چالش --}}
                @if ($challenge->tips->isNotEmpty())
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <span class="grid size-9 place-items-center rounded-2xl bg-amber-100 text-base">💡</span>
                            <h2 class="text-lg">نکته‌های این چالش</h2>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($challenge->tips as $tip)
                                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-tl from-amber-50 to-white p-5 ring-1 ring-amber-200/70">
                                    <div class="relative flex items-start gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white text-lg shadow-sm ring-1 ring-amber-200/70">{{ $tip->emoji }}</span>

                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-amber-900">{{ $tip->title }}</p>
                                            @if ($tip->body)
                                                <p class="mt-2 text-xs leading-6 text-amber-800/80">{{ $tip->body }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- پرسش و پاسخ --}}
                @if ($challenge->publishedQuestions->isNotEmpty())
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <span class="grid size-9 place-items-center rounded-2xl bg-sky-100 text-base">❓</span>
                            <h2 class="text-lg">پرسش و پاسخ</h2>
                        </div>

                        <div class="space-y-3">
                            @foreach ($challenge->publishedQuestions as $question)
                                <details class="group overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-900/5">
                                    <summary class="flex cursor-pointer items-start gap-3 px-5 py-4">
                                        <span class="min-w-0 flex-1 text-sm font-medium text-slate-800">{{ $question->question }}</span>
                                        <span class="mt-0.5 text-slate-400 transition group-open:rotate-180">⌄</span>
                                    </summary>

                                    <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4 text-xs leading-7 text-slate-600">
                                        {!! $question->answer ? \App\Support\RichText::toHtml($question->answer) : 'پاسخ این پرسش هنوز ثبت نشده است.' !!}
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ستون کنار --}}
            <aside class="no-print space-y-6 lg:sticky lg:top-24 lg:self-start">
                <div class="card text-center">
                    <x-progress-ring :percent="$progress" :class="$style['stroke']" caption="پیشرفت من" />

                    <div class="mt-6 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-2xl bg-slate-50 px-3 py-3">
                            <p class="text-[11px] text-slate-500">روزهای پیوسته</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($participant->currentStreak()) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-3 py-3">
                            <p class="text-[11px] text-slate-500">بهترین زنجیره</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($participant->bestStreak()) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-3 py-3">
                            <p class="text-[11px] text-slate-500">کار انجام‌شده</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($completed) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-3 py-3">
                            <p class="text-[11px] text-slate-500">روز باقی‌مانده</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">
                                {{ $challenge->daysRemaining() === null ? '—' : fa_num($challenge->daysRemaining()) }}
                            </p>
                        </div>
                    </div>
                </div>

                @if ($challenge->attachments->isNotEmpty())
                    <div class="card">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <span>📎</span> فایل‌های این چالش
                        </h2>

                        <ul class="mt-4 space-y-2">
                            @foreach ($challenge->attachments as $attachment)
                                <li>
                                    <a href="{{ $attachment->url() }}" class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-base ring-1 ring-slate-900/5">{{ $attachment->icon() }}</span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm text-slate-800">{{ $attachment->title }}</span>
                                            <span class="mt-0.5 block text-[11px] text-slate-400">{{ $attachment->sizeForHumans() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card" id="note">
                    <h2 class="text-sm font-semibold text-slate-900">یادداشت من</h2>
                    <p class="mt-1.5 text-xs text-slate-500">هر چیزی که می‌خواهی درباره این چالش یادت بماند.</p>

                    <form method="POST" action="{{ route('participant.note', $participant) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')

                        <textarea name="note" rows="4" class="input resize-y" placeholder="مثلاً: امروز تمرین‌ها را با انرژی بیشتری انجام دادم...">{{ old('note', $participant->note) }}</textarea>

                        <button type="submit" class="btn btn-primary w-full">ذخیره یادداشت</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="text-sm font-semibold text-slate-900">لینک اختصاصی شما</h2>
                    <p class="mt-1.5 text-xs leading-6 text-slate-500">
                        این نشانی مخصوص خودت است؛ آن را ذخیره کن تا هر وقت خواستی داشبوردت را باز کنی. این لینک را جایی منتشر نکن.
                    </p>

                    <div class="mt-4 space-y-2">
                        <input id="my-link" type="text" readonly value="{{ $participant->dashboardUrl() }}" class="input text-xs" dir="ltr">

                        <div class="flex gap-2">
                            <button type="button" class="btn btn-primary flex-1" data-copy-target="#my-link">کپی لینک</button>
                            <button type="button" class="btn btn-white" data-print>چاپ</button>
                        </div>
                    </div>
                </div>

                <div class="card bg-slate-900 ring-slate-900">
                    <p class="text-sm font-semibold text-white">یادت باشد</p>
                    <p class="mt-2 text-xs leading-6 text-white/70">
                        لازم نیست چیزی را وارد کنی یا ثبت‌نام کنی؛ فقط همین لینک را نگه دار. مسئول چالش هم پیشرفتت را می‌بیند.
                    </p>
                    <a href="{{ $challenge->publicUrl() }}" class="btn bg-white/10 mt-4 w-full text-white hover:bg-white/20">صفحه چالش</a>
                </div>
            </aside>
        </div>
    </section>
@endsection
