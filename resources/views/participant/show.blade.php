@extends('layouts.public')

@php
    $style = $challenge->style();
    $progress = $participant->progressPercent();
    $completed = $participant->completedCount();
    $total = $participant->totalTasks();
    $initial = mb_substr(trim($participant->name), 0, 1);
    $openQuestions = $challenge->questions
        ->where('is_published', true)
        ->filter(fn ($question) => $challenge->isDayUnlocked($question->day_number));
    $answeredQuestions = $myAnswers->only($openQuestions->pluck('id')->all())->count();
    $todayDay = $challenge->currentDayNumber();
    $todayCounts = $todayDay
        ? $challenge->dayCounts($todayDay)
        : ['tasks' => 0, 'questions' => 0, 'total' => 0];
@endphp

@section('title', 'داشبورد '.$participant->name.' — '.$challenge->title)

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="mx-auto max-w-5xl px-4 pt-12 sm:px-6">
        <x-flash />

        {{-- سرصفحه اختصاصی --}}
        <div class="mt-4 overflow-hidden rounded-[2rem] bg-paper shadow-sm ring-1 ring-line">
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

                    @if ($challenge->releasesDaily())
                        <p class="mt-2 text-[11px] text-white/70">روزهای چالش یکی‌یکی باز می‌شوند 🔒</p>
                    @endif
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
                            <p class="mt-1.5 text-xs leading-6 text-muted">
                                {{ fa_num($completed) }} از {{ fa_num($total) }} کار انجام شده است.
                                @if ($challenge->questionsCount() > 0)
                                    · {{ fa_num($challenge->questionsCount()) }} پرسش و پاسخ هم در این چالش هست
                                    <span class="text-forest">({{ fa_num($answeredQuestions) }} پاسخ داده‌ای)</span>.
                                @endif
                                @if ($challenge->currentDayNumber())
                                    امروز روز {{ fa_num($challenge->currentDayNumber()) }} این چالش است.
                                @endif
                            </p>
                        </div>

                        <span class="badge bg-cream text-ink/80 ring-line">
                            🔥 {{ fa_num($participant->currentStreak()) }} روز پیوسته
                        </span>
                    </div>

                    <div class="mt-5">
                        <x-progress-bar :percent="$progress" :class="$style['bar']" height="h-3" show-label />
                    </div>
                </div>

                {{-- وضعیت روز جاری و قفل روزهای بعدی --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-4 rounded-3xl bg-paper p-5 shadow-sm ring-1 ring-line">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-jade/10 text-lg">📅</span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted">روز جاری چالش</p>
                            <p class="mt-0.5 text-sm font-semibold text-forest">
                                @if ($todayDay)
                                    روز {{ fa_num($todayDay) }} از {{ fa_num($challenge->totalDays()) }}
                                @else
                                    چالش هنوز شروع نشده است
                                @endif
                            </p>
                            <p class="mt-0.5 text-[11px] text-muted">{{ fa_date(now(), true) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-3xl bg-paper p-5 shadow-sm ring-1 ring-line">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-gold/20 text-lg">{{ $challenge->lockedDaysCount() > 0 ? '🔒' : '🎉' }}</span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted">روزهای قفل‌شده</p>
                            @if ($challenge->lockedDaysCount() > 0)
                                <p class="mt-0.5 text-sm font-semibold text-forest">
                                    {{ fa_num($challenge->lockedDaysCount()) }} روز
                                </p>
                                <p class="mt-0.5 text-[11px] text-muted">
                                    روز بعدی {{ $challenge->nextUnlockDate() ? fa_date($challenge->nextUnlockDate(), true) : '' }} باز می‌شود
                                </p>
                            @else
                                <p class="mt-0.5 text-sm font-semibold text-forest">همه روزها باز است</p>
                                <p class="mt-0.5 text-[11px] text-muted">هر وقت خواستی کارها را انجام بده</p>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($todayDay && $challenge->isDayUnlocked($todayDay))
                    <a href="#day-{{ $todayDay }}"
                       class="flex items-center justify-between gap-4 rounded-3xl bg-gradient-to-tl {{ $style['gradient'] }} px-6 py-5 text-white shadow-sm">
                        <span class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-2xl bg-white/15 text-lg backdrop-blur">🎯</span>
                            <span>
                                <span class="block text-sm font-semibold">کارهای امروز را ببین</span>
                                <span class="mt-0.5 block text-xs text-white/80">
                                    روز {{ fa_num($todayDay) }} ·
                                    {{ fa_num($todayCounts['tasks']) }} کار@if ($todayCounts['questions'] > 0) · {{ fa_num($todayCounts['questions']) }} پرسش @endif
                                </span>
                            </span>
                        </span>
                        <span class="text-xl">↓</span>
                    </a>
                @endif

                @forelse ($challenge->contentByDay(true) as $day => $content)
                    @php
                        $tasks = $content['tasks'];
                        $dayQuestions = $content['questions'];
                        $dayNumber = $day > 0 ? (int) $day : null;
                        $locked = ! $challenge->isDayUnlocked($dayNumber);
                        $unlockLabel = $challenge->unlockLabel($dayNumber);
                        $isToday = $dayNumber !== null && $challenge->currentDayNumber() === $dayNumber;
                        $doneInDay = $tasks->filter(fn ($task) => $participant->isTaskDone($task))->count();
                        $dayComplete = $doneInDay === $tasks->count();
                    @endphp

                    <div id="day-{{ $day }}" class="overflow-hidden rounded-3xl bg-paper shadow-sm ring-1 {{ $isToday ? 'ring-2 ring-jade' : 'ring-line' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl text-xs font-bold {{ $locked ? 'bg-line/60 text-muted' : ($dayComplete ? 'bg-leaf text-white' : $style['bg'].' '.$style['text']) }}">
                                    {{ $locked ? '🔒' : ($dayComplete ? '✓' : fa_num($day > 0 ? $day : '★')) }}
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

                                    @if ($locked && $unlockLabel)
                                        <p class="mt-1 text-[11px] text-muted">{{ $unlockLabel }} باز می‌شود</p>
                                    @endif
                                </div>
                            </div>

                            @unless ($locked)
                                <span class="text-xs {{ $dayComplete ? 'text-forest' : 'text-muted' }}">
                                    {{ fa_num($doneInDay) }} از {{ fa_num($tasks->count()) }} کار
                                    @if ($dayQuestions->isNotEmpty())
                                        · {{ fa_num($dayQuestions->count()) }} پرسش
                                    @endif
                                </span>
                            @endunless
                        </div>

                        @if ($locked)
                            <div class="locked-panel m-3">
                                <p class="text-sm font-medium text-forest">این روز هنوز باز نشده است</p>
                                <p class="mt-1 text-xs leading-6 text-muted">
                                    {{-- کارها و پرسش‌های این روز روزانه آزاد می‌شوند؛ نمی‌توانی از قبل ببینی‌شان --}}
                                    @if ($unlockLabel)
                                        از {{ $unlockLabel }} می‌توانی کارها و پرسش‌های این روز را ببینی.
                                    @else
                                        این روز به‌زودی باز می‌شود.
                                    @endif
                                </p>

                                @if ($tasks->isNotEmpty() || $dayQuestions->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                                        @if ($tasks->isNotEmpty())
                                            <span class="badge bg-gold/15 text-ink ring-gold/50">📝 {{ fa_num($tasks->count()) }} کار در انتظار</span>
                                        @endif

                                        @if ($dayQuestions->isNotEmpty())
                                            <span class="badge bg-gold/15 text-ink ring-gold/50">❓ {{ fa_num($dayQuestions->count()) }} پرسش در انتظار</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                        <div class="space-y-2 p-3">
                            @foreach ($tasks as $task)
                                @php($done = $participant->isTaskDone($task))

                                <form id="task-{{ $task->id }}" method="POST" action="{{ route('participant.tasks.toggle', [$participant, $task]) }}">
                                    @csrf

                                    <button type="submit"
                                            class="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-right transition {{ $done ? 'bg-leaf/10 hover:bg-leaf/10' : 'bg-cream hover:bg-line/60' }}"
                                            aria-pressed="{{ $done ? 'true' : 'false' }}">
                                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg text-xs font-bold transition {{ $done ? 'bg-leaf text-white' : 'bg-paper text-transparent ring-1 ring-line' }}">
                                            ✓
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block text-sm {{ $done ? 'text-muted/80 line-through' : 'font-medium text-ink' }}">
                                                {{ $task->title }}
                                            </span>

                                            @if ($task->description)
                                                <span class="mt-1 block text-xs leading-6 {{ $done ? 'text-muted/80' : 'text-muted' }}">
                                                    {{ $task->description }}
                                                </span>
                                            @endif
                                        </span>
                                    </button>
                                </form>
                            @endforeach

                            @if ($dayQuestions->isNotEmpty())
                                <div class="rounded-2xl bg-cream/70 px-4 py-4">
                                    <p class="flex items-center gap-2 text-xs font-semibold text-forest">
                                        <span>❓</span> پرسش و پاسخ این روز
                                        <span class="font-normal text-muted">— روی هر پرسش بزن و پاسخت را بنویس</span>
                                    </p>

                                    <div class="mt-3 space-y-2">
                                        @foreach ($dayQuestions as $question)
                                            @php($myAnswer = $myAnswers->get($question->id))

                                            <details id="question-{{ $question->id }}" class="group overflow-hidden rounded-2xl bg-paper ring-1 ring-line">
                                                <summary class="flex cursor-pointer items-start gap-3 px-4 py-3">
                                                    <span class="min-w-0 flex-1 text-sm font-medium text-ink">{{ $question->question }}</span>

                                                    @if ($myAnswer)
                                                        <span class="badge shrink-0 bg-leaf/10 text-forest ring-leaf/30">✓ پاسخ دادی</span>
                                                    @endif

                                                    <span class="mt-0.5 text-muted/80 transition group-open:rotate-180">⌄</span>
                                                </summary>

                                                <div class="space-y-4 border-t border-line bg-cream/70 px-4 py-4">
                                                    @if ($question->answer)
                                                        <div>
                                                            <p class="text-[11px] font-semibold text-forest">پاسخ مسئول چالش</p>
                                                            <div class="mt-1 text-xs leading-7 text-ink/80">
                                                                {!! \App\Support\RichText::toHtml($question->answer) !!}
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <form method="POST" action="{{ route('participant.questions.answer', [$participant, $question]) }}" class="space-y-3">
                                                        @csrf

                                                        <div>
                                                            <label class="label" for="answer-{{ $question->id }}">پاسخ تو به این پرسش</label>
                                                            <textarea id="answer-{{ $question->id }}" name="body" rows="3" class="input resize-y"
                                                                      placeholder="هر چه دوست داری بنویس؛ فقط مسئول چالش می‌بیندش.">{{ old('body', $myAnswer?->body) }}</textarea>
                                                            <p class="hint">
                                                                @if ($myAnswer)
                                                                    پاسخ ثبت شده است؛ می‌توانی ویرایشش کنی. اگر کامل پاکش کنی، پاسخت برداشته می‌شود.
                                                                @else
                                                                    پاسخت فقط برای مسئول چالش نمایش داده می‌شود و در صفحه عمومی سایت دیده نمی‌شود.
                                                                @endif
                                                            </p>
                                                        </div>

                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                {{ $myAnswer ? 'ذخیره ویرایش' : 'ثبت پاسخ' }}
                                                            </button>

                                                            @if ($myAnswer)
                                                                <span class="text-[11px] text-muted/80">
                                                                    آخرین ویرایش: {{ fa_datetime($myAnswer->updated_at) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </form>
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($tasks->isEmpty() && $dayQuestions->isEmpty())
                                <p class="rounded-2xl bg-cream px-4 py-5 text-center text-xs text-muted">محتوای این روز به‌زودی اضافه می‌شود.</p>
                            @endif
                        </div>
                        @endif
                    </div>
                @empty
                    <div class="card text-center text-sm text-muted">
                        هنوز کاری برای این چالش ثبت نشده است. کمی بعد دوباره سر بزن.
                    </div>
                @endforelse

                {{-- نکات چالش --}}
                @if ($challenge->tips->isNotEmpty())
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <span class="grid size-9 place-items-center rounded-2xl bg-gold/20 text-base">💡</span>
                            <h2 class="text-lg">نکته‌های این چالش</h2>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($challenge->tips as $tip)
                                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-tl from-gold/15 to-paper p-5 ring-1 ring-gold/50">
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

            </div>

            {{-- ستون کنار --}}
            <aside class="no-print space-y-6 lg:sticky lg:top-24 lg:self-start">
                <div class="card text-center">
                    <x-progress-ring :percent="$progress" :class="$style['stroke']" caption="پیشرفت من" />

                    <div class="mt-6 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-2xl bg-cream px-3 py-3">
                            <p class="text-[11px] text-muted">روزهای پیوسته</p>
                            <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($participant->currentStreak()) }}</p>
                        </div>
                        <div class="rounded-2xl bg-cream px-3 py-3">
                            <p class="text-[11px] text-muted">بهترین زنجیره</p>
                            <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($participant->bestStreak()) }}</p>
                        </div>
                        <div class="rounded-2xl bg-cream px-3 py-3">
                            <p class="text-[11px] text-muted">کار انجام‌شده</p>
                            <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($completed) }}</p>
                        </div>
                        <div class="rounded-2xl bg-cream px-3 py-3">
                            <p class="text-[11px] text-muted">پرسش پاسخ‌داده</p>
                            <p class="mt-1 text-lg font-bold text-ink">
                                {{ fa_num($answeredQuestions) }}<span class="text-sm font-normal text-muted"> / {{ fa_num($openQuestions->count()) }}</span>
                            </p>
                        </div>
                        <div class="rounded-2xl bg-cream px-3 py-3">
                            <p class="text-[11px] text-muted">روز باقی‌مانده</p>
                            <p class="mt-1 text-lg font-bold text-ink">
                                {{ $challenge->daysRemaining() === null ? '—' : fa_num($challenge->daysRemaining()) }}
                            </p>
                        </div>
                    </div>
                </div>

                @if ($challenge->attachments->isNotEmpty())
                    <div class="card">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                            <span>📎</span> فایل‌های این چالش
                        </h2>

                        <ul class="mt-4 space-y-2">
                            @foreach ($challenge->attachments as $attachment)
                                <li>
                                    <a href="{{ $attachment->url() }}" class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 transition hover:bg-line/60">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-base ring-1 ring-line">{{ $attachment->icon() }}</span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm text-ink">{{ $attachment->title }}</span>
                                            <span class="mt-0.5 block text-[11px] text-muted/80">{{ $attachment->sizeForHumans() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card" id="note">
                    <h2 class="text-sm font-semibold text-ink">یادداشت من</h2>
                    <p class="mt-1.5 text-xs text-muted">هر چیزی که می‌خواهی درباره این چالش یادت بماند.</p>

                    <form method="POST" action="{{ route('participant.note', $participant) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')

                        <textarea name="note" rows="4" class="input resize-y" placeholder="مثلاً: امروز تمرین‌ها را با انرژی بیشتری انجام دادم...">{{ old('note', $participant->note) }}</textarea>

                        <button type="submit" class="btn btn-primary w-full">ذخیره یادداشت</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="text-sm font-semibold text-ink">لینک اختصاصی شما</h2>
                    <p class="mt-1.5 text-xs leading-6 text-muted">
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

                <div class="card bg-forest ring-forest">
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
