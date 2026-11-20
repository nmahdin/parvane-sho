@extends('layouts.public')

@php
    $style = $challenge->style();
    $questions = $challenge->questions->where('is_published', true);
    $attachments = $challenge->attachments;
    $plan = $challenge->dayPlan(true);
    $todayDay = $challenge->currentDayNumber();
    $todayUnlocked = $todayDay !== null && $challenge->isDayUnlocked($todayDay);

    // روزها را به «باز» و «قفل» تقسیم می‌کنیم تا قفل‌ها در یک نوار جمع شوند
    $openDayNumbers = [];
    $lockedDayNumbers = [];

    foreach ($plan as $dayKey => $content) {
        $number = (int) $dayKey;

        if ($challenge->isDayUnlocked($number > 0 ? $number : null)) {
            $openDayNumbers[] = $number;
        } else {
            $lockedDayNumbers[] = $number;
        }
    }

@endphp

@section('title', $challenge->title.' — '.($site['site_name'] ?? config('panel.site_name')))
@section('description', $challenge->tagline ?: $challenge->title)

@section('content')
    {{-- تا کامل بارگذاری شدن صفحه (تصویرها و صوت‌ها) این چیپ دیده می‌شود --}}
    <div id="page-loading-chip" class="page-chip" hidden>در حال بارگذاری…</div>
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
        <nav class="no-print text-xs text-hint">
            <a href="{{ route('home') }}" class="hover:text-brand-700">خانه</a>
            <span class="mx-2">/</span>
            <a href="{{ route('challenges.index') }}" class="hover:text-brand-700">چالش‌ها</a>
            <span class="mx-2">/</span>
            <span class="text-ink">{{ $challenge->title }}</span>
        </nav>

        <div class="mt-4 overflow-hidden rounded-[1.75rem] bg-paper surface">
            <div class="bg-gradient-to-tl {{ $style['gradient'] }} px-5 py-7 text-white sm:px-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-2xl">
                        <span class="grid size-12 place-items-center rounded-2xl bg-white/15 text-xl text-white backdrop-blur"><x-challenge-icon :value="$challenge->emoji" size="size-7" /></span>
                        <h1 class="mt-4 text-2xl text-white sm:text-3xl">{{ $challenge->title }}</h1>
                    </div>

                    <span class="badge bg-white/15 text-white ring-white/25">{{ $challenge->statusLabel() }}</span>
                </div>

                <dl class="mt-5 flex flex-wrap gap-2 border-t border-white/20 pt-5 text-[11px]">
                    <div class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5">
                        <dt class="text-white/70">شروع:</dt>
                        <dd class="font-medium">{{ $challenge->starts_on ? fa_date($challenge->starts_on) : '—' }}</dd>
                    </div>
                    <div class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5">
                        <dt class="text-white/70">پایان:</dt>
                        <dd class="font-medium">{{ $challenge->ends_on ? fa_date($challenge->ends_on) : '—' }}</dd>
                    </div>
                    <div class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5">
                        <dt class="text-white/70">تعداد روزها:</dt>
                        <dd class="font-medium">{{ fa_num($challenge->totalDays()) }} روز</dd>
                    </div>
                    <div class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5">
                        <dt class="text-white/70">شرکت‌کننده‌ها:</dt>
                        <dd class="font-medium">{{ fa_num($challenge->participants_count) }} نفر</dd>
                    </div>

                    @if (\App\Models\Setting::showAdminName() && $challenge->admin)
                        <div class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5">
                            <dt class="text-white/70">برگزارکننده:</dt>
                            <dd class="font-medium">{{ $challenge->admin->name }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('participant.lookup') }}" class="btn btn-white btn-sm">ورود با کد اختصاصی</a>

                    @if ($todayUnlocked)
                        <a href="#day-{{ $todayDay }}" data-jump-day="day-{{ $todayDay }}" class="btn btn-sm bg-white/15 text-white hover:bg-white/25">
                            ⭐ کارها و پرسش‌های امروز (روز {{ fa_num($todayDay) }})
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-6 px-4 pt-10 pb-8 sm:px-6 sm:pt-12 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-5">
            {{-- فایل‌های ضمیمه در موبایل بالای صفحه می‌آیند (نسخه دسکتاپ در ستون کنار است) --}}
            <x-challenge-files :challenge="$challenge" id="files" class="lg:hidden" />

            @if ($challenge->description || $challenge->long_description || $challenge->contact_note)
                <details id="about" class="card" open>
                    <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-semibold text-forest">
                        <span>📖</span> درباره این چالش
                        <span class="ms-auto text-xs font-normal text-hint">یکی از بخش‌ها</span>
                    </summary>

                    <div class="mt-3 space-y-3">
                        @if ($challenge->description)
                            <p class="text-sm leading-8 text-ink">{{ $challenge->description }}</p>
                        @endif

                        @if ($challenge->long_description)
                            <div class="border-t border-line pt-3 text-sm leading-8 text-ink/80">
                                {!! $challenge->longDescriptionHtml() !!}
                            </div>
                        @endif

                        @if ($challenge->contact_note)
                            <div class="rail rail-about flex items-start gap-3 text-xs leading-6 text-ink">
                                <span>📞</span>
                                <span>{{ $challenge->contact_note }}</span>
                            </div>
                        @endif
                    </div>
                </details>
            @endif

            {{-- نکته‌های کلی چالش (نکته‌های هر روز داخل کارت همان روز است) --}}
            @if ($challenge->generalTips()->isNotEmpty())
                <div id="tips" class="space-y-3">
                    <h2 class="section-title text-base">💡 نکته‌های کلی چالش</h2>

                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @foreach ($challenge->generalTips() as $tip)
                            <div class="rail rail-tips">
                                <div class="flex items-start gap-2.5">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-paper text-base ring-1 ring-gold/50">{{ $tip->emoji }}</span>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-forest">{{ $tip->title }}</p>

                                        @if ($tip->body)
                                            <p class="mt-1 text-xs leading-6 text-ink/80">{{ $tip->body }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- روزها: کارها و پرسش‌های هر روز --}}
            <div id="days" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="section-title text-base">روزها، کارها و پرسش‌ها</h2>

                    <span class="shrink-0 text-[11px] text-hint">
                        {{ fa_num($challenge->tasks_count) }} کار
                        @if ($questions->isNotEmpty())
                            · {{ fa_num($questions->count()) }} پرسش
                        @endif
                    </span>
                </div>

                <p class="text-xs leading-6 text-muted">
                    داخل کارت هر روز، اول نکته‌های همان روز، بعد پرسش‌ها و بعد چالش عملی آن روز آمده است. روی هر روز بزن تا باز شود؛
                    پاسخ دادن به پرسش‌ها فقط از داشبورد اختصاصی شرکت‌کننده‌ها ممکن است.
                </p>

                @php
                    $renderedDays = 0;
                @endphp

                @foreach ($plan as $day => $content)
                    @php
                        $tasks = $content['tasks'];
                        $dayQuestions = $content['questions'];
                        $dayNumber = $day > 0 ? (int) $day : null;
                        $isToday = $dayNumber !== null && $todayDay === $dayNumber;

                        // روزهای قفل در نوار پایین جمع می‌شوند و اینجا رندر نمی‌شوند
                        if (! $challenge->isDayUnlocked($dayNumber)) {
                            continue;
                        }

                        $renderedDays++;
                    @endphp

                    <x-day-shell
                        :challenge="$challenge"
                        :day="$day"
                        :today="$isToday"
                        :open="$isToday"
                        :status="$dayQuestions->isNotEmpty() ? fa_num($dayQuestions->count()).' پرسش · '.fa_num($tasks->count()).' کار' : fa_num($tasks->count()).' کار'"
                    >
                        <x-day-tips :challenge="$challenge" :day="$dayNumber" />

                        <x-day-files :challenge="$challenge" :day="$dayNumber" />

                        @if ($dayNumber && $challenge->dayDescription($dayNumber))
                            <div class="rail rail-about text-xs leading-7 text-ink/85">
                                {!! \App\Support\RichText::toHtml($challenge->dayDescription($dayNumber)) !!}
                            </div>
                        @endif

                        @if ($dayQuestions->isNotEmpty())
                            <div class="rail rail-questions">
                                <p class="rail-label text-forest">
                                    <span>❓</span> پرسش‌های این روز
                                    <span class="font-normal text-muted">— پاسخ دادن از داشبورد اختصاصی شرکت‌کننده‌ها انجام می‌شود.</span>
                                </p>

                                <ul class="mt-2.5 space-y-2">
                                    @foreach ($dayQuestions as $question)
                                        <li class="flex items-start gap-2.5">
                                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg bg-paper text-[11px] font-bold text-jade ring-1 ring-jade/30">
                                                {{ fa_num($loop->iteration) }}
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-ink">{{ $question->question }}</p>

                                                @if ($question->asked_by)
                                                    <p class="mt-1 text-[11px] text-hint">پرسیده‌شده توسط {{ $question->asked_by }}</p>
                                                @endif

                                                @if ($question->answer)
                                                    <div class="mt-2 rounded-xl bg-paper px-3 py-2 text-xs leading-7 text-ink/80">
                                                        {!! \App\Support\RichText::toHtml($question->answer) !!}
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($tasks->isNotEmpty())
                            <div class="rail rail-tasks">
                                <p class="rail-label text-forest">
                                    <span>🎯</span> {{ $challenge->tasksHeading($dayNumber) }}
                                    <span class="font-normal text-muted">— برای تیک زدن هر کار، با کد شخصی خودت وارد داشبوردت شو.</span>
                                </p>

                                <ul class="mt-2.5 space-y-2">
                                    @foreach ($tasks as $task)
                                        <li class="flex items-start gap-2.5">
                                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg bg-paper text-[11px] font-bold text-forest ring-1 ring-gold/40">
                                                {{ fa_num($loop->iteration) }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-ink">{{ $task->title }}</p>

                                                @if ($task->description)
                                                    <p class="mt-1 text-xs leading-6 text-muted whitespace-pre-line">{{ $task->description }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($tasks->isEmpty() && $dayQuestions->isEmpty() && $challenge->tipsForDay($dayNumber)->isEmpty())
                            <p class="px-1 text-xs text-muted">محتوای این روز به‌زودی اضافه می‌شود.</p>
                        @endif
                    </x-day-shell>
                @endforeach

                @if ($renderedDays === 0)
                    <div class="card text-center text-sm text-muted">
                        کارهای این چالش هنوز ثبت نشده است.
                    </div>
                @endif

                {{-- روزهای قفل‌شده: یک نوار، بدون هیچ عددی --}}
                <x-locked-strip :challenge="$challenge" :days="collect($lockedDayNumbers)" />
            </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-28 lg:self-start">
            <div class="card space-y-4">
                <div class="flex items-center gap-4">
                    <x-progress-ring :percent="$challenge->progressPercent()" :size="86" :stroke-width="9" :class="$style['stroke']" />

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-forest">پیشرفت کلی</p>
                        <p class="mt-1 text-[11px] leading-5 text-muted">
                            درصدی از همه کارها و پرسش‌ها که شرکت‌کننده‌های این چالش تا این لحظه انجام داده‌اند.
                        </p>
                    </div>
                </div>

                @if ($attachments->isNotEmpty())
                    <div class="border-t border-line pt-4" id="files-desktop">
                        <x-challenge-files :challenge="$challenge" variant="plain" />
                    </div>
                @endif

                <div id="guide" class="border-t border-line pt-4">
                    <details>
                        <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-semibold text-forest">
                            <span>🧭</span> چطور در این چالش شرکت کنم؟
                            <span class="day-chev ms-auto" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </span>
                        </summary>

                        <ol class="mt-3 space-y-3 text-xs leading-6 text-ink/80">
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
                                <span>با آن کد وارد داشبورد خودت می‌شوی، بدون رمز و ثبت‌نام: کارهای هر روز را تیک می‌زنی و به پرسش‌های همان روز پاسخ می‌نویسی. روزهای بعدی تا تاریخ خودشان باز نمی‌شوند.</span>
                            </li>
                        </ol>
                    </details>

                    <a href="{{ route('participant.lookup') }}" class="btn btn-soft mt-4 w-full">ورود با کد اختصاصی</a>
                </div>
            </div>
        </aside>
    </section>
@endsection
