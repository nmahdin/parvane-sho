@extends('layouts.public')

@php
    $style = $challenge->style();
    $progress = $participant->progressPercent();
    $completed = $participant->completedItems();
    $total = $participant->totalItems();
    $completedTasks = $participant->completedCount();
    $dayBreakdown = $participant->dayBreakdown();
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
    <section class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 sm:pt-12">
        <x-flash />

        {{-- سرصفحه اختصاصی --}}
        <div class="mt-4 overflow-hidden rounded-[2rem] bg-paper shadow-sm ring-1 ring-line">
            <div class="flex flex-wrap items-center justify-between gap-5 bg-gradient-to-tl {{ $style['gradient'] }} px-5 py-6 text-white sm:gap-6 sm:px-10 sm:py-8">
                <div class="flex items-center gap-4">
                    <span class="grid size-12 place-items-center rounded-2xl bg-white/15 text-lg font-bold backdrop-blur sm:size-14 sm:rounded-3xl sm:text-xl">{{ $initial }}</span>
                    <div>
                        <p class="text-xs text-white/75">داشبورد اختصاصی چالش</p>
                        <h1 class="mt-1 text-xl text-white sm:text-2xl">سلام {{ $participant->name }} 👋</h1>
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

    @if ($pledgeRequired)
        {{-- تعهدنامه: اولین ورود با لینک اختصاصی --}}
        <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6" id="pledge">
            <div class="overflow-hidden rounded-[2rem] bg-paper shadow-sm ring-1 ring-line">
                <div class="bg-gradient-to-tl {{ $style['gradient'] }} px-6 py-7 text-white sm:px-10">
                    <span class="grid size-12 place-items-center rounded-2xl bg-white/15 text-xl backdrop-blur">🤝</span>
                    <h2 class="mt-4 text-lg text-white">{{ $challenge->pledgeTitle() }}</h2>
                    <p class="mt-2 text-xs leading-6 text-white/80">
                        {{ $participant->name }} جان، برای شروع «{{ $challenge->title }}» یک بار این تعهدنامه را بخوان و ثبتش کن؛
                        بعد از آن کارهای هر روز برایت باز می‌شود.
                    </p>
                </div>

                <div class="space-y-5 px-6 py-7 sm:px-10">
                    <div class="space-y-2.5 text-sm leading-8 text-ink/85">
                        @foreach (preg_split('/\r\n|\r|\n/', $challenge->pledgeText()) ?: [] as $pledgeLine)
                            @php
                                $line = trim((string) $pledgeLine);
                            @endphp

                            @if ($line === '')
                                <div class="h-1"></div>
                            @elseif (str_starts_with($line, '-'))
                                <p class="flex items-start gap-2">
                                    <span class="mt-1 text-jade">•</span>
                                    <span>{{ trim(ltrim($line, '-')) }}</span>
                                </p>
                            @else
                                <p>{{ $line }}</p>
                            @endif
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('participant.pledge', $participant) }}" class="space-y-4 border-t border-line pt-5">
                        @csrf

                        <label class="flex items-start gap-3 rounded-2xl bg-cream p-4">
                            <input type="checkbox" name="accept" value="1" required class="mt-1 size-4 rounded border-line text-jade focus:ring-jade">
                            <span class="text-xs leading-6 text-ink/85">
                                این تعهدنامه را خواندم و می‌پذیرم؛ مسئولیت کارهای هر روز را خودم به عهده می‌گیرم.
                            </span>
                        </label>

                        @error('accept')
                            <p class="text-xs text-danger">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="btn btn-primary w-full py-3">تعهدنامه را ثبت می‌کنم و شروع می‌کنم</button>
                    </form>

                    <p class="text-[11px] leading-6 text-muted">
                        بعد از ثبت، داشبوردت باز می‌شود: کارهای هر روز، پرسش‌ها، نکته‌ها، فایل‌ها و پیشرفتت.
                    </p>
                </div>
            </div>
        </section>
    @else
    <section class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            {{-- ستون کارها --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg">کارها و پرسش‌های من</h2>
                            <p class="mt-1.5 text-xs leading-6 text-muted">
                                {{ fa_num($completed) }} از {{ fa_num($total) }} مورد انجام شده است
                                @if ($challenge->questionsCount() > 0)
                                    ({{ fa_num($completedTasks) }} کار تیک‌خورده و {{ fa_num($participant->answeredQuestionsCount()) }} پرسش پاسخ‌داده‌شده از {{ fa_num($challenge->questionsCount()) }} پرسش)
                                @endif
                                .
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

                {{-- وضعیت روز جاری + فایل‌های چالش --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-4 rounded-3xl bg-paper p-5 shadow-sm ring-1 ring-line">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-jade/10 text-lg">📅</span>
                        <div class="min-w-0">
                            <p class="text-xs text-muted">روز جاری چالش</p>

                            <p class="mt-0.5 text-sm font-semibold text-forest">
                                @if ($todayDay)
                                    روز {{ fa_num($todayDay) }} از {{ fa_num($challenge->totalDays()) }}
                                    <span class="text-xs font-normal text-muted">· {{ $challenge->dayDateLabel($todayDay) }}</span>
                                @else
                                    چالش هنوز شروع نشده است
                                @endif
                            </p>

                            @if ($todayDay && $challenge->lockedDaysCount() > 0 && $challenge->nextUnlockDate())
                                <p class="mt-0.5 text-[11px] leading-5 text-muted">
                                    {{ fa_num($challenge->lockedDaysCount()) }} روز هنوز باز نشده؛ روز بعدی
                                    {{ fa_date($challenge->nextUnlockDate(), true) }} باز می‌شود.
                                </p>
                            @else
                                <p class="mt-0.5 text-[11px] leading-5 text-muted">هر روز که باز شود، کارهای همان روز همین‌جا می‌آید.</p>
                            @endif
                        </div>
                    </div>

                    @if ($challenge->attachments->isNotEmpty())
                        <div class="rounded-3xl bg-paper p-5 shadow-sm ring-1 ring-line">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-gold/20 text-lg">📎</span>
                                فایل‌های این چالش
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
                </div>

                @if ($todayDay && $challenge->isDayUnlocked($todayDay))
                    <a href="#day-{{ $todayDay }}"
                       class="flex items-center justify-between gap-4 rounded-3xl bg-gradient-to-tl {{ $style['gradient'] }} px-6 py-5 text-white shadow-sm">
                        <span class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-2xl bg-white/15 text-lg backdrop-blur">🎯</span>
                            <span>
                                <span class="block text-sm font-semibold">کارهای امروز را ببین</span>
                                <span class="mt-0.5 block text-xs text-white/80">
                                    روز {{ fa_num($todayDay) }} · {{ fa_date($challenge->dateForDay($todayDay)) }} ·
                                    {{ fa_num($todayCounts['tasks']) }} کار@if ($todayCounts['questions'] > 0) · {{ fa_num($todayCounts['questions']) }} پرسش @endif
                                </span>
                            </span>
                        </span>
                        <span class="text-xl">↓</span>
                    </a>
                @endif

                {{-- نکته‌های کلی چالش (نکته‌های هر روز داخل کارت همان روز است) --}}
                @if ($challenge->generalTips()->isNotEmpty())
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <span class="grid size-9 place-items-center rounded-2xl bg-gold/20 text-base">💡</span>
                            <h2 class="text-lg">نکته‌های کلی چالش</h2>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($challenge->generalTips() as $tip)
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

                @forelse ($challenge->dayPlan(true) as $day => $content)
                    @php
                        $tasks = $content['tasks'];
                        $dayQuestions = $content['questions'];
                        $dayNumber = $day > 0 ? (int) $day : null;
                        $locked = ! $challenge->isDayUnlocked($dayNumber);
                        $unlockLabel = $challenge->unlockLabel($dayNumber);
                        $isToday = $dayNumber !== null && $challenge->currentDayNumber() === $dayNumber;
                        $dayRow = $dayBreakdown[(int) $day] ?? null;
                        $doneInDay = $dayRow['done'] ?? 0;
                        $totalInDay = $dayRow['total'] ?? 0;
                        $dayComplete = (bool) ($dayRow['complete'] ?? false);
                    @endphp

                    <div id="day-{{ $day }}" class="overflow-hidden rounded-3xl bg-paper shadow-sm ring-1 {{ $isToday ? 'ring-2 ring-jade' : 'ring-line' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl text-xs font-bold {{ $locked ? 'bg-line/60 text-muted' : ($dayComplete ? 'bg-leaf text-white' : $style['bg'].' '.$style['text']) }}">
                                    {{ $locked ? '🔒' : ($dayComplete ? '✓' : fa_num($day > 0 ? $day : '★')) }}
                                </span>

                                <div>
                                    <h3 class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink">
                                        {{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}

                                        @if ($day > 0)
                                            <span class="text-[11px] font-normal text-muted">· {{ fa_date($challenge->dateForDay((int) $day)) }}</span>
                                        @endif

                                        @if ($isToday)
                                            <span class="badge bg-jade/10 text-jade ring-jade/30">امروز</span>
                                        @elseif ($locked)
                                            <span class="badge bg-gold/15 text-ink ring-gold/50">قفل</span>
                                        @endif
                                    </h3>

                                    @if ($dayNumber && ! $locked && $challenge->dayTitle($dayNumber))
                                        <p class="mt-1 text-xs font-semibold text-forest">{{ $challenge->dayTitle($dayNumber) }}</p>
                                    @endif

                                    @if ($locked && $unlockLabel)
                                        <p class="mt-1 text-[11px] text-muted">{{ $unlockLabel }} باز می‌شود</p>
                                    @endif
                                </div>
                            </div>

                            @unless ($locked)
                                <span class="text-xs {{ $dayComplete ? 'text-forest' : 'text-muted' }}">
                                    {{ fa_num($doneInDay) }} از {{ fa_num($totalInDay) }} مورد
                                    @if ($dayQuestions->isNotEmpty())
                                        · {{ fa_num($dayRow['questions_done'] ?? 0) }} از {{ fa_num($dayQuestions->count()) }} پرسش
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
                            </div>
                        @else
                        <x-day-tips :challenge="$challenge" :day="$dayNumber" :divider="false" />

                        @if ($dayNumber && $challenge->dayDescription($dayNumber))
                            <div class="border-b border-line/60 bg-cream/60 px-4 py-4 text-xs leading-7 text-ink/85 sm:px-5">
                                {!! \App\Support\RichText::toHtml($challenge->dayDescription($dayNumber)) !!}
                            </div>
                        @endif

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
                                                            <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto">
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

                            @if ($tasks->isEmpty() && $dayQuestions->isEmpty() && $challenge->tipsForDay($dayNumber)->isEmpty())
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

            </div>

            {{-- ستون کنار --}}
            <aside class="no-print space-y-6 lg:sticky lg:top-24 lg:self-start">
                <div class="card text-center">
                    <x-progress-ring :percent="$progress" :class="$style['stroke']" caption="پیشرفت (کار + پرسش)" />

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
                            <p class="text-[11px] text-muted">مورد انجام‌شده</p>
                            <p class="mt-1 text-lg font-bold text-ink">
                                {{ fa_num($completed) }}<span class="text-sm font-normal text-muted"> / {{ fa_num($total) }}</span>
                            </p>
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
    @endif
@endsection
