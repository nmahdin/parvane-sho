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
    <section class="card" id="challenge-stats">
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
        <div>
            <h2 class="text-base">روزهای چالش</h2>
            <p class="mt-1 text-xs leading-6 text-muted">
                روزهای چالش <span class="font-semibold text-ink">خودکار از تاریخ شروع و پایان</span> ساخته می‌شوند و از همان اول فعال‌اند
                (روز ۱ = تاریخ شروع چالش)؛ لازم نیست اول کاری بسازی تا روز ظهور کند.
                روی هر روز بزن تا باز شود و برای همان روز <span class="font-semibold text-ink">عنوان و توضیحات</span> بگذاری و
                <span class="font-semibold text-ink">کار</span>، <span class="font-semibold text-ink">پرسش</span>،
                <span class="font-semibold text-ink">نکته</span> و <span class="font-semibold text-ink">فایل</span> همان روز را
                از دکمه‌های داخل باکس همان روز (مودال) اضافه کنی.
            </p>

            @if ($challenge->releasesDaily())
                <p class="mt-3 rounded-xl bg-gold/15 px-3 py-2 text-xs leading-6 text-ink ring-1 ring-gold/50">
                    🔒 آزادسازی روزبه‌روز فعال است؛ محتوای هر روز تا تاریخ همان روز برای شرکت‌کننده‌ها پنهان می‌ماند.
                    خودت به عنوان مدیر همه روزها را می‌بینی.
                </p>
            @endif
        </div>

        <div class="mt-6 space-y-5">
            @foreach ($days as $day)
                @php($dayNumber = (int) $day['number'])
                @php($isGeneral = (bool) $day['is_general'])
                @php($dayDate = $day['date_label'])
                @php($tasksOfDay = $day['tasks'])
                @php($questionsOfDay = $day['questions'])
                @php($dayTips = $day['tips'])
                @php($dayFiles = $day['files'] ?? collect())

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
                                @if (($day['counts']['files'] ?? 0) > 0) · {{ fa_num($day['counts']['files']) }} فایل @endif
                            </span>
                            <span class="text-hint transition group-open:rotate-180">⌄</span>
                        </span>
                    </summary>

                    {{-- افزودن به همین روز: چهار دکمهٔ ثابت؛ هر کدام مودال خودش را
                         باز می‌کند. روز از خود دکمه می‌آید و مودال‌ها فیلد انتخاب روز ندارند؛
                         بعد از هر ذخیره فرم خالی می‌شود و مودال باز می‌ماند. --}}
                    <div class="flex flex-wrap items-center gap-2 border-t border-line bg-cream/70 px-4 py-3 sm:px-5">
                        <button type="button" class="btn btn-primary btn-sm"
                                data-open-modal="modal-content"
                                data-modal-day="{{ $isGeneral ? '' : $dayNumber }}"
                                data-modal-type="task"
                                data-modal-title="افزودن کار (چالش عملی)">📝 افزودن کار (چالش عملی)</button>

                        <button type="button" class="btn btn-white btn-sm"
                                data-open-modal="modal-content"
                                data-modal-day="{{ $isGeneral ? '' : $dayNumber }}"
                                data-modal-type="question"
                                data-modal-title="افزودن پرسش">❓ افزودن پرسش</button>

                        <button type="button" class="btn btn-gold btn-sm"
                                data-open-modal="modal-tip"
                                data-modal-day="{{ $isGeneral ? '' : $dayNumber }}"
                                data-modal-title="افزودن نکته">💡 افزودن نکته</button>

                        @unless ($isGeneral)
                            <button type="button" class="btn btn-white btn-sm"
                                    data-open-modal="modal-file"
                                    data-modal-day="{{ $dayNumber }}"
                                    data-modal-title="افزودن فایل">📎 افزودن فایل</button>
                        @endunless

                        <button type="button" class="btn btn-ghost btn-sm ms-auto"
                                data-open-modal="modal-bulk"
                                data-modal-day="{{ $isGeneral ? '' : $dayNumber }}">📃 چند کار یک‌جا</button>
                    </div>

                    @unless ($isGeneral)
                        {{-- عنوان و توضیحات همین روز --}}
                        <div class="border-t border-line bg-paper px-4 py-3 sm:px-5">
                            <details id="day-{{ $dayNumber }}-title" class="day-group rounded-2xl bg-cream/70 ring-1 ring-line">
                                <summary class="day-group-head">
                                    <span>✏️ عنوان و توضیحات این روز</span>
                                    <span class="font-normal text-muted">
                                        @if ($day['title'])
                                            — {{ $day['title'] }}
                                        @else
                                            — هنوز چیزی نوشته نشده
                                        @endif
                                    </span>
                                </summary>

                                <form method="POST" action="{{ route('panel.days.update', [$challenge, $dayNumber]) }}" data-ajax-save class="space-y-3 border-t border-line px-4 py-4 text-right">
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
                    <div class="border-t border-line bg-paper px-4 py-3 sm:px-5">
                        <details id="day-{{ $dayNumber }}-tasks" class="day-group rounded-2xl bg-cream/70 ring-1 ring-line">
                            <summary class="day-group-head">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span>📝</span> کارهای این روز
                                    <span class="badge bg-jade/10 text-jade ring-jade/30">{{ fa_num($tasksOfDay->count()) }}</span>
                                </span>

                                <span class="flex flex-wrap items-center gap-2">
                                    <button type="button" class="btn btn-white btn-sm"
                                            data-open-modal="modal-content"
                                            data-modal-day="{{ $isGeneral ? '' : $dayNumber }}"
                                            data-modal-type="task">+ کار</button>

                                    <button type="button" class="btn btn-white btn-sm"
                                            data-open-modal="modal-bulk"
                                            data-modal-day="{{ $isGeneral ? '' : $dayNumber }}">+ چند کار</button>
                                </span>
                            </summary>

                            <div class="border-t border-line">
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
                                                                            <span class="text-hint">{{ fa_datetime($row['completed_at']) }}</span>
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
                                            <form method="POST" action="{{ route('panel.tasks.move', [$challenge, $task, 'up']) }}" data-ajax-save>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="بالا">↑</button>
                                            </form>

                                            <form method="POST" action="{{ route('panel.tasks.move', [$challenge, $task, 'down']) }}" data-ajax-save>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="پایین">↓</button>
                                            </form>

                                            <details>
                                                <summary class="btn btn-ghost btn-sm list-none">ویرایش</summary>

                                                <form method="POST" action="{{ route('panel.tasks.update', [$challenge, $task]) }}" data-ajax-save class="mt-3 grid w-full max-w-xl gap-3 rounded-2xl bg-cream p-4 text-right sm:grid-cols-2">
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

                                            <form method="POST" action="{{ route('panel.tasks.destroy', [$challenge, $task]) }}" data-ajax-save data-confirm="این کار حذف شود؟ تیک‌های مربوط به آن هم پاک می‌شود.">
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
                            </div>
                        </details>
                    </div>

                    {{-- پرسش‌های همین روز --}}
                    <div class="border-t border-line bg-paper px-4 py-3 sm:px-5">
                        <details id="day-{{ $dayNumber }}-questions" class="day-group rounded-2xl bg-cream/70 ring-1 ring-line">
                            <summary class="day-group-head">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span>❓</span> پرسش و پاسخ این روز
                                    <span class="badge bg-jade/10 text-jade ring-jade/30">{{ fa_num($questionsOfDay->count()) }}</span>
                                </span>

                                <button type="button" class="btn btn-white btn-sm"
                                        data-open-modal="modal-content"
                                        data-modal-day="{{ $isGeneral ? '' : $dayNumber }}"
                                        data-modal-type="question">+ پرسش</button>
                            </summary>

                            <div class="space-y-3 border-t border-line px-4 py-4">
                    @if ($questionsOfDay->isEmpty())
                        <p class="text-[11px] text-muted">برای این روز هنوز پرسشی ثبت نشده است.</p>
                    @else
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
                                                <p class="mt-1 text-[11px] text-hint">پرسیده‌شده توسط {{ $question->asked_by }}</p>
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

                                                                    <span class="text-[11px] text-hint">{{ fa_datetime($answerRow->updated_at) }}</span>
                                                                </div>

                                                                <p class="mt-1.5 whitespace-pre-line text-xs leading-6 text-ink/80">{{ $answerRow->body }}</p>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </details>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <form method="POST" action="{{ route('panel.questions.move', [$challenge, $question, 'up']) }}" data-ajax-save>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="بالا">↑</button>
                                            </form>

                                            <form method="POST" action="{{ route('panel.questions.move', [$challenge, $question, 'down']) }}" data-ajax-save>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm" aria-label="پایین">↓</button>
                                            </form>

                                            <form method="POST" action="{{ route('panel.questions.publish', [$challenge, $question]) }}" data-ajax-save>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-white btn-sm">{{ $question->is_published ? 'پنهان کن' : 'نمایش بده' }}</button>
                                            </form>

                                            <details>
                                                <summary class="btn btn-ghost btn-sm list-none">ویرایش</summary>

                                                <form method="POST" action="{{ route('panel.questions.update', [$challenge, $question]) }}" data-ajax-save class="mt-3 grid w-full max-w-lg gap-3 rounded-2xl bg-paper p-4 text-right ring-1 ring-line">
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

                                            <form method="POST" action="{{ route('panel.questions.destroy', [$challenge, $question]) }}" data-ajax-save data-confirm="این پرسش حذف شود؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                    @endif
                            </div>
                        </details>
                    </div>

                    {{-- نکته‌های این روز --}}
                    <div class="border-t border-line bg-gold/5 px-4 py-3 sm:px-5">
                        <details id="day-{{ $dayNumber }}-tips" class="day-group rounded-2xl bg-paper/80 ring-1 ring-gold/40">
                            <summary class="day-group-head">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span>💡</span> {{ $isGeneral ? 'نکته‌های کلی' : 'نکته‌های این روز' }}
                                    <span class="badge bg-gold/15 text-gold-ink ring-gold/50">{{ fa_num($dayTips->count()) }}</span>
                                </span>

                                <button type="button" class="btn btn-gold btn-sm"
                                        data-open-modal="modal-tip"
                                        data-modal-day="{{ $isGeneral ? '' : $dayNumber }}">+ نکته</button>
                            </summary>

                            <div class="border-t border-gold/30 px-4 py-4">
                                @if ($dayTips->isEmpty())
                                    <p class="text-[11px] text-muted">برای {{ $isGeneral ? 'کل چالش' : 'این روز' }} هنوز نکته‌ای نوشته نشده است.</p>
                                @else
                                    <div class="space-y-3">
                                        @foreach ($dayTips as $tip)
                                            <x-tip-item :challenge="$challenge" :tip="$tip" :day-choices="$dayChoices" with-day-picker />
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </details>
                    </div>

                    {{-- فایل‌ها و فایل‌های صوتی همین روز --}}
                    @unless ($isGeneral)
                        <div class="border-t border-line bg-paper px-4 py-3 sm:px-5">
                            <details id="day-{{ $dayNumber }}-files" class="day-group rounded-2xl bg-cream/70 ring-1 ring-line">
                                <summary class="day-group-head">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span>📎</span> فایل‌ها و فایل‌های صوتی این روز
                                        <span class="badge bg-jade/10 text-jade ring-jade/30">{{ fa_num($dayFiles->count()) }}</span>
                                    </span>

                                    <button type="button" class="btn btn-white btn-sm"
                                            data-open-modal="modal-file"
                                            data-modal-day="{{ $dayNumber }}">+ فایل</button>
                                </summary>

                                <div class="border-t border-line px-4 py-4">
                            @if ($dayFiles->isEmpty())
                                <p class="mt-4 rounded-2xl bg-cream px-4 py-5 text-center text-[11px] text-muted">برای این روز هنوز فایلی گذاشته نشده است.</p>
                            @else
                                <ul class="mt-4 space-y-2">
                                    @foreach ($dayFiles as $dayFile)
                                        <li class="rounded-2xl bg-cream p-3 ring-1 ring-line">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="flex min-w-0 flex-1 items-start gap-3">
                                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-base ring-1 ring-line">{{ $dayFile->icon() }}</span>

                                                    <div class="min-w-0">
                                                        <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-ink">
                                                            {{ $dayFile->title }}
                                                            <span class="badge {{ $dayFile->is_audio ? 'bg-gold/15 text-gold-ink ring-gold/50' : 'bg-jade/10 text-jade ring-jade/30' }}">
                                                                {{ $dayFile->is_audio ? '🎧 فایل صوتی' : '📎 فایل' }}
                                                            </span>
                                                        </p>

                                                        <p class="mt-1 text-[11px] text-hint" dir="ltr">
                                                            {{ $dayFile->original_name }} · {{ $dayFile->sizeForHumans() }}
                                                        </p>

                                                        <p class="mt-1 text-[11px] text-muted">
                                                            @if ($dayFile->is_audio)
                                                                🎧 {{ fa_num($dayFile->plays) }} پخش
                                                            @else
                                                                ⬇️ {{ fa_num($dayFile->downloads) }} دانلود
                                                            @endif
                                                        </p>

                                                        @if ($dayFile->is_audio)
                                                            <div class="mt-3 max-w-md">
                                                                <x-audio-player :file="$dayFile" :day="$dayNumber" />
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-1">
                                                    <a href="{{ $dayFile->url() }}" target="_blank" class="btn btn-white btn-sm">دیدن</a>

                                                    <details>
                                                        <summary class="btn btn-ghost btn-sm list-none">ویرایش</summary>

                                                        <form method="POST" action="{{ route('panel.day-files.update', [$challenge, $dayFile]) }}" data-ajax-save class="mt-3 grid w-full max-w-md gap-3 rounded-2xl bg-paper p-4 text-right ring-1 ring-line">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="grid gap-3 sm:grid-cols-2">
                                                                <div>
                                                                    <label class="label">تاریخ روز</label>
                                                                    <input type="text" name="date" dir="ltr" data-jalali-picker class="input text-center"
                                                                           value="{{ \App\Support\Jalali::inputValue($challenge->dateForDay($dayFile->day_number)) }}">
                                                                    <p class="hint">با تغییر تاریخ، فایل به آن روز منتقل می‌شود.</p>
                                                                </div>

                                                                <div>
                                                                    <label class="label">عنوان</label>
                                                                    <input type="text" name="title" value="{{ $dayFile->title }}" required class="input">
                                                                </div>
                                                            </div>

                                                            <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                                                        </form>
                                                    </details>

                                                    <form method="POST" action="{{ route('panel.day-files.destroy', [$challenge, $dayFile]) }}" data-ajax-save data-confirm="فایل «{{ $dayFile->title }}» حذف شود؟">
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
                                </div>
                            </details>
                        </div>
                    @endunless

                </details>
            @endforeach
        </div>
    </section>

    {{-- مودال‌های افزودن: یک فرم مشترک برای همه روزها --}}
    <dialog id="modal-content" class="panel-modal">
        <div class="panel-modal-box">
            <div class="panel-modal-head">
                <h3 class="text-sm font-semibold text-ink"><span data-modal-heading>افزودن کار یا پرسش</span> <span data-modal-day-label class="font-normal text-muted"></span></h3>
                <button type="button" class="btn btn-ghost btn-sm" data-close-modal aria-label="بستن">✕</button>
            </div>

            <form method="POST" action="{{ route('panel.days.store', $challenge) }}" data-day-type-form data-ajax-save data-modal-form data-stay-open class="grid gap-4 text-right">
                @csrf
                <input type="hidden" name="day_number" value="" data-modal-day-input>

                <div data-day-type-switch>
                    <span class="label">چه چیزی اضافه شود؟</span>
                    <div class="flex flex-wrap gap-2">
                        <label data-day-type-label="task" class="cursor-pointer rounded-2xl px-4 py-2 text-xs ring-1 ring-line transition">
                            <input type="radio" name="type" value="task" class="sr-only" checked> 📝 کار
                        </label>

                        <label data-day-type-label="question" class="cursor-pointer rounded-2xl px-4 py-2 text-xs ring-1 ring-line transition">
                            <input type="radio" name="type" value="question" class="sr-only"> ❓ پرسش و پاسخ
                        </label>
                    </div>
                </div>

                <div data-day-group="task" class="grid gap-4">
                    <div>
                        <label class="label">عنوان کار</label>
                        <input name="title" type="text" class="input" placeholder="مثلاً ۲۰ دقیقه پیاده‌روی">
                    </div>

                    <div>
                        <label class="label">توضیح کار (اختیاری)</label>
                        <textarea name="description" rows="2" class="input resize-y" placeholder="جزئیات یا شرط انجام کار..."></textarea>
                    </div>
                </div>

                <div data-day-group="question" class="hidden gap-4">
                    <div>
                        <label class="label">متن پرسش</label>
                        <input name="question" type="text" class="input" placeholder="مثلاً اگر یک روز را از دست بدهم چه می‌شود؟">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                        <div>
                            <label class="label">پرسنده (اختیاری)</label>
                            <input name="asked_by" type="text" class="input" placeholder="نام شرکت‌کننده">
                        </div>

                        <label class="flex items-center gap-2 pb-3 text-xs text-ink/80">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" checked class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                            نمایش در صفحه چالش
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-close-modal>بستن</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="modal-bulk" class="panel-modal">
        <div class="panel-modal-box">
            <div class="panel-modal-head">
                <h3 class="text-sm font-semibold text-ink">افزودن چند کار یک‌جا <span data-modal-day-label class="font-normal text-muted"></span></h3>
                <button type="button" class="btn btn-ghost btn-sm" data-close-modal aria-label="بستن">✕</button>
            </div>

            <form method="POST" action="{{ route('panel.tasks.bulk', $challenge) }}" data-ajax-save data-modal-form data-stay-open class="space-y-3 text-right">
                @csrf
                <input type="hidden" name="day_number" value="" data-modal-day-input>

                <label class="label">هر خط یک کار</label>
                <textarea name="items" rows="5" required class="input resize-y" placeholder="۲۰ دقیقه پیاده‌روی&#10;نوشتن سه جمله شکرگزاری&#10;۱۰ دقیقه تمرین تمرکز"></textarea>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn btn-primary">افزودن کارها</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-close-modal>بستن</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="modal-file" class="panel-modal">
        <div class="panel-modal-box">
            <div class="panel-modal-head">
                <h3 class="text-sm font-semibold text-ink">افزودن فایل یا فایل صوتی <span data-modal-day-label class="font-normal text-muted"></span></h3>
                <button type="button" class="btn btn-ghost btn-sm" data-close-modal aria-label="بستن">✕</button>
            </div>

            <form method="POST" action="{{ route('panel.day-files.store', $challenge) }}" data-ajax-save data-modal-form data-stay-open enctype="multipart/form-data" class="space-y-3 text-right">
                @csrf
                <input type="hidden" name="day_number" value="" data-modal-day-input required>

                <div>
                    <label class="label">عنوان فایل (اختیاری)</label>
                    <input name="title" type="text" maxlength="190" class="input" placeholder="مثلاً صوت روز سوم">
                </div>

                <div>
                    <label class="label">فایل یا فایل صوتی</label>
                    <input name="file" type="file" required class="input py-2.5 text-xs file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-brand-700">
                    <p class="hint">mp3، m4a، wav، ogg برای صوت (تا ۶۰ مگابایت)؛ pdf، تصویر، zip و ... برای فایل (تا ۸ مگابایت). صوتی‌ها با پخش‌کننده خود سایت پخش می‌شوند.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn btn-primary">آپلود و افزودن</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-close-modal>بستن</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="modal-tip" class="panel-modal">
        <div class="panel-modal-box">
            <div class="panel-modal-head">
                <h3 class="text-sm font-semibold text-ink">افزودن نکته <span data-modal-day-label class="font-normal text-muted"></span></h3>
                <button type="button" class="btn btn-ghost btn-sm" data-close-modal aria-label="بستن">✕</button>
            </div>

            <x-tip-form :challenge="$challenge" modal data-modal-form data-stay-open class="text-right" />

            <div class="mt-3 text-left">
                <button type="button" class="btn btn-ghost btn-sm" data-close-modal>بستن</button>
            </div>
        </div>
    </dialog>

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
                                            <span class="text-[11px] text-hint" dir="ltr">{{ $attachment->original_name }}</span>
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
                                                <span class="text-[11px] text-hint">{{ $participant->contact }}</span>
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
