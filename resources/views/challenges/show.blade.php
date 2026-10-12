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
                        <dd class="mt-1 font-medium">{{ fa_num($challenge->totalDays()) }} روز</dd>
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

            {{-- نکته‌های کلی چالش: نمایش ویژه (نکته‌های هر روز داخل کارت همان روز است) --}}
            @if ($challenge->generalTips()->isNotEmpty())
                <div>
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid size-9 place-items-center rounded-2xl bg-gold/20 text-base">💡</span>
                        <h2 class="text-lg">نکته‌های کلی چالش</h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($challenge->generalTips() as $tip)
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

            {{-- روزها: کارها و پرسش‌های هر روز --}}
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg">روزها، کارها و پرسش‌ها</h2>
                        <p class="mt-1 text-xs leading-6 text-muted">داخل کارت هر روز، اول نکته‌های همان روز، بعد پرسش‌ها و بعد چالش عملی آن روز آمده است. پاسخ دادن به پرسش‌ها فقط از داشبورد اختصاصی شرکت‌کننده‌ها ممکن است.</p>
                    </div>

                    <span class="shrink-0 text-xs text-muted">
                        {{ fa_num($challenge->tasks_count) }} کار
                        @if ($questions->isNotEmpty())
                            · {{ fa_num($questions->count()) }} پرسش
                        @endif
                    </span>
                </div>

                @forelse ($challenge->dayPlan(true) as $day => $content)
                    @php
                        $tasks = $content['tasks'];
                        $dayQuestions = $content['questions'];
                        $dayNumber = $day > 0 ? (int) $day : null;
                        $locked = ! $challenge->isDayUnlocked($dayNumber);
                        $unlockLabel = $challenge->unlockLabel($dayNumber);
                        $isToday = $dayNumber !== null && $challenge->currentDayNumber() === $dayNumber;
                    @endphp

                    <div id="day-{{ $day }}" class="overflow-hidden rounded-3xl bg-paper shadow-sm ring-1 {{ $isToday ? 'ring-2 ring-jade' : 'ring-line' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5 sm:px-6 sm:py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl text-xs font-bold {{ $locked ? 'bg-line/60 text-muted' : $style['bg'].' '.$style['text'] }}">
                                    {{ $locked ? '🔒' : fa_num($day > 0 ? $day : '★') }}
                                </span>

                                <div>
                                    <h3 class="flex items-center gap-2 text-sm font-semibold text-ink">
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

                                    @if ($locked)
                                        <p class="mt-1 text-[11px] text-muted">
                                            {{ $unlockLabel ? $unlockLabel.' باز می‌شود' : 'به‌زودی باز می‌شود' }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @unless ($locked)
                                <span class="text-xs text-muted">
                                    @if ($dayQuestions->isNotEmpty()){{ fa_num($dayQuestions->count()) }} پرسش · @endif{{ fa_num($tasks->count()) }} کار
                                </span>
                            @endunless
                        </div>

                        @if ($locked)
                            <div class="locked-panel m-4">
                                <p class="text-sm font-medium text-forest">پرسش‌ها و چالش عملی این روز هنوز آزاد نشده‌اند</p>
                                <p class="mt-1 text-xs leading-6 text-muted">
                                    روزهای چالش یکی‌یکی باز می‌شوند؛ برای دیدن محتوای این روز
                                    @if ($unlockLabel)
                                        تا {{ $unlockLabel }} صبر کن.
                                    @else
                                        کمی صبر کن.
                                    @endif
                                </p>
                            </div>
                        @else
                            <x-day-tips :challenge="$challenge" :day="$dayNumber" :divider="false" />

                            @if ($dayNumber && $challenge->dayDescription($dayNumber))
                                <div class="border-b border-line/60 bg-cream/60 px-4 py-4 text-xs leading-7 text-ink/85 sm:px-6">
                                    {!! \App\Support\RichText::toHtml($challenge->dayDescription($dayNumber)) !!}
                                </div>
                            @endif

                            @if ($dayQuestions->isNotEmpty())
                                <div class="border-t border-line bg-cream/60 px-6 py-5">
                                    <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-forest">
                                        <span>❓</span> پرسش‌های این روز
                                        <span class="font-normal text-muted">— پاسخ دادن به پرسش‌ها از داشبورد اختصاصی شرکت‌کننده‌ها انجام می‌شود.</span>
                                    </p>

                                    <ul class="mt-3 space-y-3">
                                        @foreach ($dayQuestions as $question)
                                            <li class="rounded-2xl bg-paper p-4 ring-1 ring-line">
                                                <div class="flex items-start gap-3">
                                                    <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-xl bg-jade/10 text-[11px] font-bold text-jade">
                                                        {{ fa_num($loop->iteration) }}
                                                    </span>

                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-medium text-ink">{{ $question->question }}</p>

                                                        @if ($question->asked_by)
                                                            <p class="mt-1 text-[11px] text-muted/80">پرسیده‌شده توسط {{ $question->asked_by }}</p>
                                                        @endif

                                                        @if ($question->answer)
                                                            <div class="mt-2 rounded-xl bg-cream/70 px-3 py-2 text-xs leading-7 text-ink/80">
                                                                {!! \App\Support\RichText::toHtml($question->answer) !!}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if ($tasks->isNotEmpty())
                                <div class="border-t border-line px-4 py-5 sm:px-6">
                                    <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-forest">
                                        <span>🎯</span> {{ $challenge->tasksHeading($dayNumber) }}
                                        <span class="font-normal text-muted">— برای تیک زدن هر کار، با کد شخصی خودت وارد داشبوردت شو.</span>
                                    </p>

                                    <ul class="mt-3 space-y-3">
                                        @foreach ($tasks as $task)
                                            <li class="flex items-start gap-3 rounded-2xl bg-cream/60 p-4 ring-1 ring-line">
                                                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-xl bg-gold/25 text-[11px] font-bold text-ink">
                                                    {{ fa_num($loop->iteration) }}
                                                </span>

                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-ink">{{ $task->title }}</p>
                                                    @if ($task->description)
                                                        <p class="mt-1.5 text-xs leading-6 text-muted">{{ $task->description }}</p>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($tasks->isEmpty() && $dayQuestions->isEmpty() && $challenge->tipsForDay($dayNumber)->isEmpty())
                                <p class="bg-paper px-6 py-5 text-xs text-muted">محتوای این روز به‌زودی اضافه می‌شود.</p>
                            @endif
                        @endif
                    </div>
                @empty
                    <div class="card text-center text-sm text-muted">
                        کارهای این چالش هنوز ثبت نشده است.
                    </div>
                @endforelse
            </div>

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
                        <span>با آن کد وارد داشبورد خودت می‌شوی، بدون رمز و ثبت‌نام: کارهای هر روز را تیک می‌زنی و به پرسش‌های همان روز پاسخ می‌نویسی. روزهای بعدی تا تاریخ خودشان قفل می‌مانند.</span>
                    </li>
                </ol>

                <a href="{{ route('participant.lookup') }}" class="btn btn-soft mt-5 w-full">ورود با کد اختصاصی</a>
            </div>
        </aside>
    </section>
@endsection
