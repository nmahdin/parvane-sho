@extends('layouts.panel')

@php($style = $challenge->style())

@section('title', $challenge->title)
@section('page-title', 'مدیریت چالش: '.$challenge->title)
@section('page-subtitle', fa_num($participants->count()).' شرکت‌کننده · '.fa_num($tasksCount).' کار · '.fa_num($questionsCount).' پرسش')

@section('page-actions')
    <a href="{{ $challenge->publicUrl() }}" target="_blank" class="btn btn-white btn-sm">مشاهده در سایت ↗</a>
    <a href="{{ route('panel.challenges.export', $challenge) }}" class="btn btn-white btn-sm">⬇️ خروجی اکسل</a>
    <a href="{{ route('panel.challenges.edit', $challenge) }}" class="btn btn-soft btn-sm">ویرایش مشخصات</a>
@endsection

@section('content')
    {{-- خلاصه چالش --}}
    <section class="card">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="flex items-start gap-4">
                <span class="grid size-14 shrink-0 place-items-center rounded-3xl bg-gradient-to-tl {{ $style['gradient'] }} text-2xl text-white">{{ $challenge->emoji }}</span>

                <div>
                    <h2 class="text-lg">{{ $challenge->title }}</h2>
                    <p class="mt-1.5 max-w-2xl text-xs leading-6 text-muted">{{ $challenge->tagline ?: 'توضیح کوتاهی ثبت نشده است.' }}</p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="badge {{ $challenge->statusClasses() }}">{{ $challenge->statusLabel() }}</span>
                        <span class="badge bg-cream text-ink/80 ring-line">{{ $challenge->dateRangeLabel() }}</span>
                        <span class="badge bg-cream text-ink/80 ring-line" dir="ltr">/{{ $challenge->slug }}</span>
                        <span class="badge {{ $challenge->pledgeEnabled() ? 'bg-leaf/10 text-forest ring-leaf/30' : 'bg-cream text-muted ring-line' }}">
                            🤝 تعهدنامه: {{ $challenge->pledgeEnabled() ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('panel.challenges.publish', $challenge) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-white btn-sm">{{ $challenge->is_published ? 'پنهان کردن از سایت' : 'انتشار در سایت' }}</button>
                </form>

                <form method="POST" action="{{ route('panel.challenges.destroy', $challenge) }}" data-confirm="کل چالش «{{ $challenge->title }}» با همه کارها، نام‌ها و پیشرفت‌ها حذف شود؟ این کار برگشت‌پذیر نیست.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">حذف چالش</button>
                </form>
            </div>
        </div>

        <div class="mt-6 grid gap-4 border-t border-line pt-6 sm:grid-cols-3 xl:grid-cols-6">
            <div>
                <p class="text-xs text-muted">روزها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($challenge->totalDays()) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">کارها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($tasksCount) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">پرسش و پاسخ‌ها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($questionsCount) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">کل موارد (کار + پرسش)</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($tasksCount + $questionsCount) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">شرکت‌کننده‌ها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($participants->count()) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">پیشرفت کلی</p>
                <div class="mt-2"><x-progress-bar :percent="$challenge->progressPercent()" :class="$style['bar']" show-label /></div>
            </div>
        </div>
    </section>

    {{-- ناوبری بخش‌های چالش --}}
    <nav class="flex gap-2 overflow-x-auto rounded-3xl bg-paper p-3 shadow-sm ring-1 ring-line">
        @foreach ([
            ['#days', '📅 روزها، کارها و پرسش‌ها'],
            ['#tips', '💡 نکات'],
            ['#files', '📎 فایل‌ها'],
            ['#participants', '👥 شرکت‌کننده‌ها'],
        ] as [$href, $label])
            <a href="{{ $href }}" class="shrink-0 rounded-2xl px-4 py-2 text-xs text-ink/80 transition hover:bg-brand-50 hover:text-brand-700">{{ $label }}</a>
        @endforeach
    </nav>

    {{-- باکس روزها: برای هر روز، عنوان و توضیح و کار و پرسش و نکته --}}
    <span id="days" class="block scroll-mt-24"></span>
    <section class="card scroll-mt-24" id="tasks">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-base">روزهای چالش</h2>
                <p class="mt-1 text-xs leading-6 text-muted">
                    روزهای چالش <span class="font-semibold text-ink">خودکار از تاریخ شروع و پایان</span> ساخته می‌شوند و از همان اول فعال‌اند
                    (روز ۱ = تاریخ شروع چالش)؛ لازم نیست اول کاری بسازی تا روز ظهور کند.
                    روی هر روز بزن تا باز شود و برای همان روز، <span class="font-semibold text-ink">عنوان و توضیحات</span> بگذاری و
                    <span class="font-semibold text-ink">کار</span>، <span class="font-semibold text-ink">پرسش</span> و
                    <span class="font-semibold text-ink">نکته</span> تعریف کنی.
                </p>

                @if ($challenge->releasesDaily())
                    <p class="mt-3 rounded-xl bg-gold/15 px-3 py-2 text-xs leading-6 text-ink ring-1 ring-gold/50">
                        🔒 آزادسازی روزبه‌روز فعال است؛ محتوای هر روز تا تاریخ همان روز برای شرکت‌کننده‌ها پنهان می‌ماند.
                        خودت به عنوان مدیر همه روزها را می‌بینی.
                    </p>
                @endif
            </div>

            <details class="w-full sm:w-auto">
                <summary class="btn btn-primary btn-sm list-none">+ افزودن سریع به یک روز دیگر</summary>

                <form method="POST" action="{{ route('panel.days.store', $challenge) }}" data-day-type-form
                      class="mt-4 grid w-full gap-4 rounded-2xl bg-cream p-4 text-right sm:max-w-2xl">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="day-date">تاریخ روز</label>
                            <input id="day-date" name="date" type="text" value="{{ old('date', $defaultDate) }}" dir="ltr"
                                   data-jalali-picker autocomplete="off" class="input text-center"
                                   data-day-preview="#day-preview"
                                   data-day-start="{{ \App\Support\Jalali::inputValue($challenge->dayOneDate()) }}"
                                   data-day-start-label="{{ fa_date($challenge->dayOneDate(), true) }}">

                            <p class="hint" id="day-preview">روز خودکار حساب می‌شود.</p>
                            <p class="hint">پیشنهاد: {{ $defaultDayLabel }} (روز {{ fa_num($defaultDay) }})</p>
                        </div>

                        <div>
                            <span class="label">روزهای چالش — روی هر روز بزن تا در فیلد تاریخ بیاید</span>

                            <div class="flex max-h-44 flex-wrap gap-1.5 overflow-y-auto rounded-2xl bg-paper p-2 ring-1 ring-line">
                                @foreach ($dayChoices as $choice)
                                    <button type="button"
                                            data-fill-target="#day-date"
                                            data-fill-value="{{ $choice['input'] }}"
                                            class="rounded-xl px-2.5 py-1.5 text-[11px] ring-1 transition
                                                {{ $choice['is_today']
                                                    ? 'bg-jade/15 font-semibold text-jade ring-jade/40'
                                                    : ($choice['is_past'] ? 'bg-cream text-ink/80 ring-line hover:bg-gold/15' : 'bg-paper text-ink/80 ring-line hover:bg-gold/15') }}">
                                        روز {{ fa_num($choice['day']) }}
                                        · {{ $choice['label'] }}
                                        @if ($choice['is_today'])
                                            · امروز
                                        @elseif ($choice['is_locked'])
                                            · 🔒
                                        @endif
                                        @if ($choice['count'] > 0)
                                            · {{ fa_num($choice['count']) }} مورد
                                        @endif
                                    </button>
                                @endforeach

                                <button type="button" data-fill-target="#day-date" data-fill-value=""
                                        class="rounded-xl bg-paper px-2.5 py-1.5 text-[11px] text-ink/80 ring-1 ring-line transition hover:bg-gold/15">
                                    بدون روز مشخص
                                </button>
                            </div>

                            <p class="hint">روزهای گذشته هم در همین فهرست‌اند؛ کافی است روی روز مورد نظر بزنی. اگر تاریخی قبل از شروع چالش بدهی، پیام راهنما می‌بینی.</p>
                        </div>
                    </div>

                    <div>
                        <span class="label">چه چیزی اضافه شود؟</span>
                        <div class="flex flex-wrap gap-2">
                            <label data-day-type-label="task" class="cursor-pointer rounded-2xl px-4 py-2 text-xs ring-1 ring-line transition">
                                <input type="radio" name="type" value="task" class="sr-only" @checked(old('type', 'task') !== 'question')> 📝 کار
                            </label>

                            <label data-day-type-label="question" class="cursor-pointer rounded-2xl px-4 py-2 text-xs ring-1 ring-line transition">
                                <input type="radio" name="type" value="question" class="sr-only" @checked(old('type') === 'question')> ❓ پرسش و پاسخ
                            </label>
                        </div>
                    </div>

                    <div data-day-group="task" class="grid gap-4">
                        <div>
                            <label class="label" for="day-task-title">عنوان کار</label>
                            <input id="day-task-title" name="title" type="text" class="input" placeholder="مثلاً ۲۰ دقیقه پیاده‌روی">
                        </div>

                        <div>
                            <label class="label" for="day-task-description">توضیح کار (اختیاری)</label>
                            <textarea id="day-task-description" name="description" rows="2" class="input resize-y" placeholder="جزئیات یا شرط انجام کار..."></textarea>
                        </div>
                    </div>

                    <div data-day-group="question" class="hidden gap-4">
                        <div>
                            <label class="label" for="day-question-text">متن پرسش</label>
                            <input id="day-question-text" name="question" type="text" class="input" placeholder="مثلاً اگر یک روز را از دست بدهم چه می‌شود؟">
                        </div>

                        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                            <div>
                                <label class="label" for="day-question-by">پرسنده (اختیاری)</label>
                                <input id="day-question-by" name="asked_by" type="text" class="input" placeholder="نام شرکت‌کننده">
                            </div>

                            <label class="flex items-center gap-2 pb-3 text-xs text-ink/80">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" checked class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                                نمایش در صفحه چالش
                            </label>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">ذخیره در این روز</button>
                    </div>
                </form>
            </details>
        </div>

        <div class="mt-6 space-y-5">
            @foreach ($days as $day)
                @php($dayNumber = (int) $day['number'])
                @php($isGeneral = (bool) $day['is_general'])
                @php($dayDate = $day['date_label'])
                @php($tasksOfDay = $day['tasks'])
                @php($questionsOfDay = $day['questions'])
                @php($dayTips = $day['tips'])

                <details id="day-{{ $dayNumber }}" class="group overflow-hidden rounded-3xl ring-1 ring-line" @if ($day['open']) open @endif>
                    <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-3 bg-cream px-4 py-3 sm:px-5">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink">
                            <span class="grid size-8 shrink-0 place-items-center rounded-xl text-xs font-bold {{ $day['is_today'] ? 'bg-jade text-white' : ($day['has_content'] ? 'bg-paper text-ink ring-1 ring-line' : 'bg-line/60 text-muted') }}">
                                {{ $isGeneral ? '★' : ($day['unlocked'] ? fa_num($dayNumber) : '🔒') }}
                            </span>

                            {{ $isGeneral ? 'بدون روز مشخص' : 'روز '.fa_num($dayNumber) }}

                            @if ($dayDate)
                                <span class="text-xs font-normal text-muted">· {{ $dayDate }}</span>
                            @endif

                            @if ($day['is_today'])
                                <span class="badge bg-jade/10 text-jade ring-jade/30">امروز</span>
                            @elseif (! $isGeneral && ! $day['unlocked'])
                                <span class="badge bg-gold/15 text-ink ring-gold/50">باز نشده</span>
                            @endif

                            @if ($day['title'])
                                <span class="text-xs font-normal text-ink/70">· {{ $day['title'] }}</span>
                            @endif
                        </span>

                        <span class="flex items-center gap-3 text-xs text-muted">
                            <span>
                                {{ fa_num($day['counts']['tasks']) }} کار
                                @if ($day['counts']['questions'] > 0) · {{ fa_num($day['counts']['questions']) }} پرسش @endif
                                @if ($day['counts']['tips'] > 0) · {{ fa_num($day['counts']['tips']) }} نکته @endif
                            </span>
                            <span class="text-muted/70 transition group-open:rotate-180">⌄</span>
                        </span>
                    </summary>

                    @unless ($isGeneral)
                        {{-- عنوان و توضیحات همین روز --}}
                        <div class="border-t border-line/60 bg-paper px-4 py-3 sm:px-5">
                            <details class="rounded-2xl bg-cream/70 ring-1 ring-line">
                                <summary class="cursor-pointer px-4 py-2.5 text-xs font-semibold text-ink">
                                    ✏️ عنوان و توضیحات این روز
                                    <span class="font-normal text-muted">
                                        @if ($day['title'])
                                            — {{ $day['title'] }}
                                        @else
                                            — هنوز چیزی نوشته نشده
                                        @endif
                                    </span>
                                </summary>

                                <form method="POST" action="{{ route('panel.days.update', [$challenge, $dayNumber]) }}" class="space-y-3 border-t border-line px-4 py-4 text-right">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="label" for="day-title-{{ $dayNumber }}">عنوان این روز</label>
                                        <input id="day-title-{{ $dayNumber }}" name="title" type="text" maxlength="190" value="{{ $day['title'] }}" class="input" placeholder="مثلاً روز شکرگزاری از آدم‌ها">
                                    </div>

                                    <div>
                                        <label class="label" for="day-description-{{ $dayNumber }}">توضیحات این روز</label>
                                        <textarea id="day-description-{{ $dayNumber }}" name="description" rows="4" class="input resize-y" placeholder="چند خط دربارهٔ هدف این روز، تمرین‌ها یا نکته‌هایش...">{{ $day['description'] }}</textarea>
                                        <p class="hint">این متن بالای کارها و پرسش‌های همان روز به شرکت‌کننده نشان داده می‌شود.</p>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm">ذخیره عنوان و توضیحات</button>
                                </form>
                            </details>
                        </div>
                    @endunless

                    {{-- کارهای این روز --}}
                    @if ($tasksOfDay->isEmpty())
                        <p class="bg-paper px-5 py-3 text-xs text-muted">برای این روز هنوز کاری ثبت نشده است.</p>
                    @else
                        <ul class="divide-y divide-line bg-paper">
                            @foreach ($tasksOfDay as $task)
                                <li class="px-5 py-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-ink">{{ $task->title }}</p>
                                            @if ($task->description)
                                                <p class="mt-1 text-xs leading-6 text-muted">{{ $task->description }}</p>
                                            @endif

                                            @php($taskStatus = $taskStatuses[$task->id] ?? null)

                                            @if ($taskStatus)
                                                <details class="mt-3">
                                                    <summary class="cursor-pointer text-xs font-medium text-brand-700">
                                                        چه کسانی انجام داده‌اند؟ ({{ fa_num(count($taskStatus['done'])) }} از {{ fa_num($taskStatus['total']) }})
                                                    </summary>

                                                    <div class="mt-3 grid gap-2 lg:grid-cols-2">
                                                        <div class="rounded-2xl bg-cream p-3 ring-1 ring-line">
                                                            <p class="text-[11px] font-semibold text-forest">انجام داده‌اند ({{ fa_num(count($taskStatus['done'])) }})</p>

                                                            @if ($taskStatus['done'] === [])
                                                                <p class="mt-2 text-[11px] text-muted">هنوز کسی این کار را تیک نزده است.</p>
                                                            @else
                                                                <ul class="mt-2 space-y-1.5">
                                                                    @foreach ($taskStatus['done'] as $row)
                                                                        <li class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
                                                                            <a href="{{ route('panel.participants.show', $row['participant']) }}"
                                                                               class="font-semibold text-forest hover:text-brand-700">{{ $row['participant']->name }}</a>
                                                                            <span class="text-muted/80">{{ fa_datetime($row['completed_at']) }}</span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>

                                                        <div class="rounded-2xl bg-paper p-3 ring-1 ring-line">
                                                            <p class="text-[11px] font-semibold text-muted">انجام نداده‌اند ({{ fa_num(count($taskStatus['pending'])) }})</p>

                                                            @if ($taskStatus['pending'] === [])
                                                                <p class="mt-2 text-[11px] text-forest">همهٔ شرکت‌کننده‌ها این کار را انجام داده‌اند.</p>
                                                            @else
                                                                <ul class="mt-2 space-y-1.5">
                                                                    @foreach ($taskStatus['pending'] as $pendingParticipant)
                                                                        <li class="text-[11px]">
                                                                            <a href="{{ route('panel.participants.show', $pendingParticipant) }}"
                                                                               class="text-ink/80 hover:text-brand-700">{{ $pendingParticipant->name }}</a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </details>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <form method="POST" action="{{ route('panel.tasks.move', [$challenge, $task, 'up']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="بالا">↑</button>
                                            </form>

                                            <form method="POST" action="{{ route('panel.tasks.move', [$challenge, $task, 'down']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="پایین">↓</button>
                                            </form>

                                            <details>
                                                <summary class="btn btn-ghost btn-sm list-none">ویرایش</summary>

                                                <form method="POST" action="{{ route('panel.tasks.update', [$challenge, $task]) }}" class="mt-3 grid w-full max-w-xl gap-3 rounded-2xl bg-cream p-4 text-right sm:grid-cols-2">
                                                    @csrf
                                                    @method('PUT')

                                                    <div>
                                                        <label class="label">تاریخ روز</label>
                                                        <input type="text" name="date" dir="ltr" data-jalali-picker class="input text-center"
                                                               value="{{ \App\Support\Jalali::inputValue($challenge->dateForDay($task->day_number ?? 1)) }}">
                                                        <p class="hint">با تغییر تاریخ، کار به آن روز منتقل می‌شود.</p>
                                                    </div>

                                                    <div>
                                                        <label class="label">عنوان</label>
                                                        <input type="text" name="title" value="{{ $task->title }}" required class="input">
                                                    </div>

                                                    <div class="sm:col-span-2">
                                                        <label class="label">توضیح</label>
                                                        <textarea name="description" rows="2" class="input resize-y">{{ $task->description }}</textarea>
                                                    </div>

                                                    <div class="sm:col-span-2">
                                                        <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                                                    </div>
                                                </form>
                                            </details>

                                            <form method="POST" action="{{ route('panel.tasks.destroy', [$challenge, $task]) }}" data-confirm="این کار حذف شود؟ تیک‌های مربوط به آن هم پاک می‌شود.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- پرسش‌های همین روز --}}
                    @if ($questionsOfDay->isNotEmpty())
                        <div class="space-y-3 border-t border-line bg-paper px-5 py-4">
                            <p class="flex items-center gap-2 text-xs font-semibold text-forest">
                                <span>❓</span> پرسش و پاسخ این روز
                            </p>

                            @foreach ($questionsOfDay as $question)
                                <div class="rounded-2xl bg-cream p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-medium text-ink">{{ $question->question }}</p>
                                                <span class="badge {{ $question->is_published ? 'bg-leaf/10 text-forest ring-leaf/30' : 'bg-line text-ink/80 ring-line' }}">
                                                    {{ $question->is_published ? 'نمایش داده می‌شود' : 'پنهان' }}
                                                </span>
                                                <span class="badge {{ $question->answers->isNotEmpty() ? 'bg-jade/10 text-jade ring-jade/30' : 'bg-cream text-muted ring-line' }}">
                                                    {{ fa_num($question->answers->count()) }} پاسخ شرکت‌کننده‌ها
                                                </span>
                                            </div>

                                            @if ($question->asked_by)
                                                <p class="mt-1 text-[11px] text-muted/80">پرسیده‌شده توسط {{ $question->asked_by }}</p>
                                            @endif

                                            @if ($question->answer)
                                                <p class="mt-2 border-s-2 border-line ps-3 text-xs leading-6 text-ink/80">
                                                    {!! \App\Support\RichText::toHtml($question->answer) !!}
                                                </p>
                                            @endif

                                            <details class="mt-3">
                                                <summary class="cursor-pointer text-xs font-medium text-brand-700">
                                                    پاسخ‌های شرکت‌کننده‌ها ({{ fa_num($question->answers->count()) }})
                                                </summary>

                                                @if ($question->answers->isEmpty())
                                                    <p class="mt-3 rounded-2xl bg-paper px-3 py-4 text-center text-[11px] text-muted ring-1 ring-line">
                                                        هنوز کسی به این پرسش پاسخ نداده است.
                                                    </p>
                                                @else
                                                    <ul class="mt-3 space-y-2">
                                                        @foreach ($question->answers as $answerRow)
                                                            <li class="rounded-2xl bg-paper p-3 ring-1 ring-line">
                                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                                    <a href="{{ route('panel.participants.show', $answerRow->participant) }}"
                                                                       class="text-xs font-semibold text-forest hover:text-brand-700">
                                                                        {{ $answerRow->participant->name }}
                                                                    </a>

                                                                    <span class="text-[11px] text-muted/80">{{ fa_datetime($answerRow->updated_at) }}</span>
                                                                </div>

                                                                <p class="mt-1.5 whitespace-pre-line text-xs leading-6 text-ink/80">{{ $answerRow->body }}</p>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </details>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <form method="POST" action="{{ route('panel.questions.move', [$challenge, $question, 'up']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="بالا">↑</button>
                                            </form>

                                            <form method="POST" action="{{ route('panel.questions.move', [$challenge, $question, 'down']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="پایین">↓</button>
                                            </form>

                                            <form method="POST" action="{{ route('panel.questions.publish', [$challenge, $question]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-white btn-sm">{{ $question->is_published ? 'پنهان کن' : 'نمایش بده' }}</button>
                                            </form>

                                            <details>
                                                <summary class="btn btn-ghost btn-sm list-none">ویرایش</summary>

                                                <form method="POST" action="{{ route('panel.questions.update', [$challenge, $question]) }}" class="mt-3 grid w-full max-w-lg gap-3 rounded-2xl bg-paper p-4 text-right ring-1 ring-line">
                                                    @csrf
                                                    @method('PUT')

                                                    <div class="grid gap-3 sm:grid-cols-2">
                                                        <div>
                                                            <label class="label">تاریخ روز</label>
                                                            <input type="text" name="date" dir="ltr" data-jalali-picker class="input text-center"
                                                                   value="{{ $question->day_number ? \App\Support\Jalali::inputValue($challenge->dateForDay($question->day_number)) : '' }}">
                                                            <p class="hint">خالی بگذاری، پرسش در «بدون روز مشخص» می‌نشیند.</p>
                                                        </div>

                                                        <div>
                                                            <label class="label">پرسنده</label>
                                                            <input type="text" name="asked_by" value="{{ $question->asked_by }}" class="input">
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="label">پرسش</label>
                                                        <input type="text" name="question" value="{{ $question->question }}" required class="input">
                                                    </div>

                                                    <label class="flex items-center gap-2 text-xs text-ink/80">
                                                        <input type="hidden" name="is_published" value="0">
                                                        <input type="checkbox" name="is_published" value="1" @checked($question->is_published) class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                                                        نمایش در صفحه چالش
                                                    </label>

                                                    <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                                                </form>
                                            </details>

                                            <form method="POST" action="{{ route('panel.questions.destroy', [$challenge, $question]) }}" data-confirm="این پرسش حذف شود؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- نکته‌های این روز --}}
                    <div class="border-t border-line/60 bg-gold/5 px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h4 class="flex flex-wrap items-center gap-2 text-xs font-semibold text-ink">
                                <span>💡</span> {{ $isGeneral ? 'نکته‌های کلی' : 'نکته‌های این روز' }}
                                <span class="font-normal text-muted">{{ fa_num($dayTips->count()) }} نکته</span>
                            </h4>

                            <details class="w-full sm:w-auto">
                                <summary class="cursor-pointer text-xs font-medium text-brand-700">+ افزودن نکته {{ $isGeneral ? 'کلی' : 'به این روز' }}</summary>

                                <x-tip-form :challenge="$challenge" :day="$isGeneral ? null : $dayNumber" class="bg-paper" />
                            </details>
                        </div>

                        @if ($dayTips->isNotEmpty())
                            <div class="mt-3 space-y-3">
                                @foreach ($dayTips as $tip)
                                    <x-tip-item :challenge="$challenge" :tip="$tip" :day-choices="$dayChoices" with-day-picker />
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- افزودن سریع به همین روز --}}
                    <div class="grid gap-3 border-t border-line bg-cream/70 px-5 py-3 lg:grid-cols-2">
                        <details>
                            <summary class="cursor-pointer text-xs font-medium text-brand-700">+ افزودن چند کار به این روز</summary>

                            <form method="POST" action="{{ route('panel.tasks.bulk', $challenge) }}" class="mt-4 space-y-3 text-right">
                                @csrf

                                @if (! $isGeneral)
                                    <input type="hidden" name="day_number" value="{{ $dayNumber }}">
                                @endif

                                <label class="label" for="bulk-{{ $dayNumber }}">هر خط یک کار</label>
                                <textarea id="bulk-{{ $dayNumber }}" name="items" rows="4" required class="input resize-y" placeholder="۲۰ دقیقه پیاده‌روی&#10;نوشتن سه جمله شکرگزاری&#10;۱۰ دقیقه تمرین تمرکز"></textarea>

                                <button type="submit" class="btn btn-primary btn-sm">افزودن به {{ $isGeneral ? 'بدون روز' : 'روز '.fa_num($dayNumber) }}</button>
                            </form>
                        </details>

                        <details>
                            <summary class="cursor-pointer text-xs font-medium text-brand-700">+ افزودن پرسش به این روز</summary>

                            <form method="POST" action="{{ route('panel.questions.store', $challenge) }}" class="mt-4 space-y-3 text-right">
                                @csrf

                                @if (! $isGeneral)
                                    <input type="hidden" name="day_number" value="{{ $dayNumber }}">
                                @endif

                                <div>
                                    <label class="label">متن پرسش</label>
                                    <input name="question" type="text" required class="input" placeholder="مثلاً این چالش برای چه کسانی مناسب است؟">
                                </div>

                                <div>
                                    <label class="label">پرسنده (اختیاری)</label>
                                    <input name="asked_by" type="text" class="input">
                                </div>

                                <label class="flex items-center gap-2 text-xs text-ink/80">
                                    <input type="checkbox" name="is_published" value="1" checked class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                                    نمایش در صفحه چالش
                                </label>

                                <button type="submit" class="btn btn-primary btn-sm">افزودن پرسش</button>
                            </form>
                        </details>
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- نکته‌های کلی چالش (بدون روز مشخص) --}}
    <section class="card scroll-mt-24" id="tips">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-base">نکته‌های کلی چالش</h2>
                <p class="mt-1 text-xs leading-6 text-muted">
                    نکته‌ها در صفحه چالش و داشبورد شرکت‌کننده‌ها با ظاهر ویژه (کارت کهربایی) نمایش داده می‌شوند.
                    برای نکته‌های مخصوص هر روز، داخل کارت همان روز در بخش «روزها» نکته اضافه کن؛
                    یا همین‌جا روز نکته را از فهرست انتخاب کن.
                </p>
            </div>
        </div>

        <x-tip-form :challenge="$challenge" :day-choices="$dayChoices" class="mt-5" />

        @if ($challenge->generalTips()->isEmpty())
            <p class="mt-5 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">
                هنوز نکتهٔ کلی‌ای اضافه نشده است. نکته‌های مخصوص هر روز را از داخل کارت همان روز در بخش «روزها» اضافه کن.
            </p>
        @else
            <div class="mt-5 space-y-3">
                @foreach ($challenge->generalTips() as $tip)
                    <x-tip-item :challenge="$challenge" :tip="$tip" :day-choices="$dayChoices" with-day-picker />
                @endforeach
            </div>
        @endif
    </section>

    {{-- فایل‌های ضمیمه --}}
    <section class="card scroll-mt-24" id="files">
        <div>
            <h2 class="text-base">فایل‌های ضمیمه چالش</h2>
            <p class="mt-1 text-xs leading-6 text-muted">
                فایل راهنما، جدول تمرین‌ها، صوت یا هر چیزی که شرکت‌کننده‌ها لازم دارند. حجم هر فایل تا ۸ مگابایت.
            </p>
        </div>

        <form method="POST" action="{{ route('panel.attachments.store', $challenge) }}" enctype="multipart/form-data" class="mt-5 grid gap-4 rounded-2xl bg-cream p-4 sm:grid-cols-[1fr_1.4fr_auto]">
            @csrf

            <div>
                <label class="label" for="file-title">عنوان فایل</label>
                <input id="file-title" name="title" type="text" class="input" placeholder="مثلاً جدول تمرین‌های هفته اول">
            </div>

            <div>
                <label class="label" for="file-input">فایل</label>
                <input id="file-input" name="file" type="file" required class="input py-2.5 text-xs file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-brand-700">
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full sm:w-auto">افزودن فایل</button>
            </div>
        </form>

        @if ($challenge->attachments->isEmpty())
            <p class="mt-5 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">هنوز فایلی ضمیمه نشده است.</p>
        @else
            <div class="table-wrap mt-5">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>فایل</th>
                            <th>حجم</th>
                            <th>دانلود</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($challenge->attachments as $attachment)
                            <tr>
                                <td data-label="">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-cream text-base">{{ $attachment->icon() }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ $attachment->url() }}" target="_blank" class="block max-w-[18rem] truncate font-medium text-ink hover:text-brand-700">{{ $attachment->title }}</a>
                                            <span class="text-[11px] text-muted/80" dir="ltr">{{ $attachment->original_name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="حجم" class="text-xs text-muted">{{ $attachment->sizeForHumans() }}</td>
                                <td data-label="دانلود" class="text-xs text-muted">{{ fa_num($attachment->downloads) }} بار</td>

                                <td data-label="">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ $attachment->url() }}" class="btn btn-ghost btn-sm">دانلود</a>

                                        <details>
                                            <summary class="btn btn-ghost btn-sm list-none">ویرایش</summary>
                                            <form method="POST" action="{{ route('panel.attachments.update', [$challenge, $attachment]) }}" class="mt-3 grid w-full max-w-sm gap-3 rounded-2xl bg-cream p-4 text-right">
                                                @csrf
                                                @method('PUT')
                                                <div>
                                                    <label class="label">عنوان</label>
                                                    <input type="text" name="title" value="{{ $attachment->title }}" required class="input">
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                                            </form>
                                        </details>

                                        <form method="POST" action="{{ route('panel.attachments.destroy', [$challenge, $attachment]) }}" data-confirm="فایل «{{ $attachment->title }}» حذف شود؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- شرکت‌کننده‌ها --}}
    <section class="card scroll-mt-24" id="participants">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-base">شرکت‌کننده‌ها ({{ fa_num($participants->count()) }})</h2>
                <p class="mt-1 text-xs leading-6 text-muted">
                    نام‌ها را خط‌به‌خط وارد کن. برای هر نفر یک لینک اختصاصی ساخته می‌شود که بدون رمز و ثبت‌نام داشبورد خودش را نشان می‌دهد.
                </p>
            </div>

            @if ($participants->isNotEmpty())
                <button type="button" class="btn btn-white btn-sm" data-copy-target="#all-links">کپی همه لینک‌ها</button>
            @endif
        </div>

        <form method="POST" action="{{ route('panel.participants.store', $challenge) }}" class="mt-5 space-y-3 rounded-2xl bg-cream p-4">
            @csrf

            <label class="label" for="names">افزودن به فهرست چالش</label>
            <textarea id="names" name="names" rows="3" class="input resize-y" placeholder="زهرا محمدی&#10;امیر رضایی&#10;نگار کریمی" data-autogrow></textarea>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary w-full sm:w-auto">افزودن به فهرست</button>
            </div>
        </form>

        @if ($participants->isEmpty())
            <div class="mt-6 rounded-2xl bg-cream p-8 text-center">
                <p class="text-2xl">👥</p>
                <p class="mt-3 text-sm text-ink/80">هنوز کسی در فهرست این چالش نیست.</p>
            </div>
        @else
            <div class="table-wrap mt-6">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>نام</th>
                            <th class="w-44">پیشرفت</th>
                            <th class="min-w-[22rem]">لینک اختصاصی</th>
                            <th>آخرین بازدید</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php($percent = $participant->progressPercent())

                            <tr>
                                <td data-label="">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-cream text-xs font-bold text-ink/80">{{ mb_substr($participant->name, 0, 1) }}</span>
                                        <div>
                                            <a href="{{ route('panel.participants.show', $participant) }}" class="block font-medium text-ink hover:text-brand-700">{{ $participant->name }}</a>
                                            @if ($participant->contact)
                                                <span class="text-[11px] text-muted/80">{{ $participant->contact }}</span>
                                            @endif

                                            @if ($challenge->pledgeEnabled())
                                                <span class="mt-1 inline-block rounded-lg px-1.5 py-0.5 text-[10px] {{ $participant->hasPledged() ? 'bg-leaf/10 text-forest' : 'bg-gold/20 text-ink' }}">
                                                    {{ $participant->hasPledged() ? 'تعهدنامه ✓' : 'تعهدنامه ✗' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td data-label="پیشرفت">
                                    <x-progress-bar :percent="$percent" :class="$style['bar']" show-label />
                                    <p class="mt-1.5 text-[11px] text-muted">{{ fa_num($participant->completedItems()) }} از {{ fa_num($participant->totalItems()) }} مورد (کار و پرسش)</p>
                                </td>

                                <td data-label="لینک اختصاصی">
                                    <div class="flex items-center gap-2">
                                        <input id="link-{{ $participant->id }}" type="text" readonly value="{{ route('participant.show', $participant) }}" class="input py-2 text-[11px]" dir="ltr">
                                        <button type="button" class="btn btn-soft btn-sm shrink-0" data-copy-target="#link-{{ $participant->id }}">کپی</button>
                                    </div>
                                </td>

                                <td data-label="آخرین بازدید" class="text-xs text-muted">
                                    {{ $participant->last_seen_at ? fa_datetime($participant->last_seen_at) : 'هنوز باز نکرده' }}
                                </td>

                                <td data-label="">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('participant.show', $participant) }}" target="_blank" class="btn btn-ghost btn-sm">داشبورد ↗</a>

                                        <form method="POST" action="{{ route('panel.participants.regenerate', $participant) }}" data-confirm="لینک جدید ساخته شود؟ لینک قبلی «{{ $participant->name }}» از کار می‌افتد.">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-ghost btn-sm">لینک جدید</button>
                                        </form>

                                        <form method="POST" action="{{ route('panel.participants.destroy', $participant) }}" data-confirm="«{{ $participant->name }}» از فهرست چالش حذف شود؟ پیشرفت او هم پاک می‌شود.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <textarea id="all-links" class="hidden" readonly>{{ $participants->map(fn ($p) => $p->name.' : '.route('participant.show', $p))->implode("\n") }}</textarea>
        @endif
    </section>
@endsection
