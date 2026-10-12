@extends('layouts.panel')

@php
    $style = $challenge->style();
    $statusInfo = $participant->activityStatus();
    $maxActivity = max(1, max($activity ?: [1]));
@endphp

@section('title', 'وضعیت '.$participant->name)
@section('page-title', 'وضعیت '.$participant->name)
@section('page-subtitle', $challenge->title.' · '.fa_num($stats['progress']).'٪ پیشرفت')

@section('page-actions')
    <a href="{{ route('participant.show', $participant) }}" target="_blank" class="btn btn-soft btn-sm">دیدن داشبورد او ↗</a>
    <a href="{{ route('panel.participants.index') }}" class="btn btn-white btn-sm">فهرست شرکت‌کننده‌ها</a>
@endsection

@section('content')
    {{-- خلاصه وضعیت --}}
    <section class="card">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="flex items-start gap-4">
                <span class="grid size-14 shrink-0 place-items-center rounded-3xl bg-gradient-to-tl {{ $style['gradient'] }} text-xl font-bold text-white">
                    {{ mb_substr($participant->name, 0, 1) }}
                </span>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg">{{ $participant->name }}</h2>
                        <span class="badge {{ $statusInfo['classes'] }}">{{ $statusInfo['label'] }}</span>
                    </div>

                    <p class="mt-1.5 text-xs text-muted">
                        <a href="{{ route('panel.challenges.show', $challenge) }}" class="hover:text-brand-700">{{ $challenge->emoji }} {{ $challenge->title }}</a>
                        @if ($participant->contact)
                            · <span dir="auto">{{ $participant->contact }}</span>
                        @endif
                    </p>

                    <p class="mt-1 text-[11px] text-muted/80">
                        آخرین بازدید: {{ $participant->last_seen_at ? fa_datetime($participant->last_seen_at) : 'هنوز باز نکرده' }}
                        @if ($stats['last_activity'])
                            · آخرین تیک: {{ fa_datetime($stats['last_activity']) }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('panel.participants.regenerate', $participant) }}" data-confirm="لینک جدید ساخته شود؟ لینک فعلی از کار می‌افتد.">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-white btn-sm">لینک جدید</button>
                </form>

                <form method="POST" action="{{ route('panel.participants.destroy', $participant) }}" data-confirm="«{{ $participant->name }}» حذف شود؟ پیشرفت او هم پاک می‌شود.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">حذف از چالش</button>
                </form>
            </div>
        </div>

        <div class="mt-6 grid gap-4 border-t border-line pt-6 sm:grid-cols-3 xl:grid-cols-6">
            <div>
                <p class="text-xs text-muted">کار انجام‌شده</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($stats['completed']) }} از {{ fa_num($stats['total']) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">روزهای کامل</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($stats['completed_days']) }} از {{ fa_num($stats['days_count']) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">روزهای از دست رفته</p>
                <p class="mt-1 text-lg font-bold {{ $stats['missed_days'] > 0 ? 'text-ink' : 'text-ink' }}">{{ fa_num($stats['missed_days']) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted">زنجیره فعلی</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($stats['streak']) }} روز</p>
            </div>
            <div>
                <p class="text-xs text-muted">بهترین زنجیره</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ fa_num($stats['best_streak']) }} روز</p>
            </div>
            <div>
                <p class="text-xs text-muted">پیشرفت</p>
                <div class="mt-2"><x-progress-bar :percent="$stats['progress']" :class="$style['bar']" show-label /></div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
        <div class="space-y-6">
            {{-- وضعیت روز به روز --}}
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base">وضعیت روز به روز</h2>
                        <p class="mt-1 text-xs text-muted">هر خانه یک روز از چالش است؛ عددها نشان می‌دهد چند کار از آن روز انجام شده.</p>
                    </div>

                    @if ($stats['current_day'])
                        <span class="badge bg-brand-50 text-brand-700 ring-brand-200">امروز: روز {{ fa_num($stats['current_day']) }}</span>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($breakdown as $day => $row)
                        @php
                            $locked = ! $challenge->isDayUnlocked($day > 0 ? (int) $day : null);
                            $tone = $row['complete']
                                ? 'bg-leaf/10 ring-leaf/30'
                                : ($row['done'] > 0 ? 'bg-gold/15 ring-gold/50' : ($row['is_past'] ? 'bg-danger-soft ring-danger/20' : 'bg-cream ring-line'));
                        @endphp

                        <div class="rounded-2xl p-3 ring-1 {{ $tone }} {{ $row['is_today'] ? 'ring-2 ring-brand-300' : '' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-ink">
                                    @if ($locked) 🔒 @endif{{ $day > 0 ? 'روز '.fa_num($day) : 'کلی' }}
                                </span>

                                @if ($locked)
                                    <span class="text-xs text-muted">قفل</span>
                                @elseif ($row['complete'])
                                    <span class="text-xs text-forest">✓ کامل</span>
                                @elseif ($row['done'] > 0)
                                    <span class="text-xs text-ink">{{ fa_num($row['done']) }}/{{ fa_num($row['total']) }}</span>
                                @elseif ($row['is_past'])
                                    <span class="text-xs text-danger">انجام نشده</span>
                                @else
                                    <span class="text-xs text-muted/80">در انتظار</span>
                                @endif
                            </div>

                            <div class="mt-2">
                                <div class="h-1.5 overflow-hidden rounded-full bg-white/70">
                                    <div class="h-full rounded-full {{ $row['complete'] ? 'bg-leaf' : 'bg-gold' }}" style="width: {{ $row['total'] ? round($row['done'] / $row['total'] * 100) : 0 }}%"></div>
                                </div>
                            </div>

                            @if ($day > 0)
                                <p class="mt-1.5 text-[10px] text-muted/80">
                                    {{ $row['is_today'] ? 'امروز · ' : '' }}{{ fa_date($challenge->dateForDay((int) $day)) }}
                                </p>
                            @elseif ($row['is_today'])
                                <p class="mt-1.5 text-[10px] text-brand-700">امروز</p>
                            @endif

                            @if ((int) ($questionCounts[$day] ?? 0) > 0)
                                <p class="mt-1 text-[10px] text-muted/80">❓ {{ fa_num((int) $questionCounts[$day]) }} پرسش</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- پاسخ‌های او به پرسش‌های چالش --}}
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base">پاسخ‌های او به پرسش‌ها</h2>
                        <p class="mt-1 text-xs leading-6 text-muted">
                            هر پاسخی که برای پرسش‌های چالش نوشته است، اینجا همراه با تاریخ ثبت می‌شود.
                        </p>
                    </div>

                    <span class="badge {{ $answers->isNotEmpty() ? 'bg-jade/10 text-jade ring-jade/30' : 'bg-cream text-muted ring-line' }}">
                        {{ fa_num($answers->count()) }} پاسخ
                    </span>
                </div>

                @if ($answers->isEmpty())
                    <p class="mt-5 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">
                        هنوز به هیچ پرسشی پاسخ نداده است.
                    </p>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach ($answers as $answer)
                            <div class="rounded-2xl bg-cream p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-ink">
                                        {{ $answer->question->question ?? 'پرسش حذف‌شده' }}
                                    </p>

                                    <span class="shrink-0 text-[11px] text-muted/80">{{ fa_datetime($answer->updated_at) }}</span>
                                </div>

                                @if ($answer->question?->day_number)
                                    <p class="mt-1 text-[11px] text-muted/80">{{ $answer->question->dayLabel() }}</p>
                                @endif

                                <p class="mt-2 whitespace-pre-line border-s-2 border-line ps-3 text-xs leading-6 text-ink/80">{{ $answer->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- نمودار فعالیت --}}
            <section class="card">
                <h2 class="text-base">فعالیت ۱۴ روز اخیر</h2>
                <p class="mt-1 text-xs text-muted">هر ستون یک روز است؛ ارتفاع نشان می‌دهد آن روز چند کار انجام داده است.</p>

                <div class="mt-6 flex items-end justify-between gap-1.5">
                    @foreach ($activity as $date => $count)
                        @php
                            $height = $count === 0 ? 6 : max(12, (int) round($count / $maxActivity * 100));
                            $isToday = \Illuminate\Support\Carbon::parse($date)->isToday();
                        @endphp

                        <div class="group flex flex-1 flex-col items-center gap-2" title="{{ fa_date($date, true) }} — {{ fa_num($count) }} کار">
                            <div class="flex w-full items-end justify-center" style="height: 120px">
                                <div class="w-full rounded-t-lg transition-all {{ $count === 0 ? 'bg-line' : 'bg-brand-500 group-hover:bg-brand-600' }}"
                                     style="height: {{ $height }}%"></div>
                            </div>

                            <span class="text-[10px] {{ $isToday ? 'font-bold text-brand-700' : 'text-muted/80' }}">{{ fa_num($count) }}</span>
                            <span class="text-[9px] text-muted/80">{{ fa_num(\Illuminate\Support\Carbon::parse($date)->format('j')) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- تیک‌زدن کارها --}}
            <section class="card">
                <h2 class="text-base">کارهای او</h2>
                <p class="mt-1 text-xs text-muted">
                    می‌توانی از اینجا هم به‌جای او تیک بزنی یا تیک را برداری (مثلاً وقتی خودش اطلاع داده است).
                </p>

                <div class="mt-5 space-y-5">
                    @forelse ($challenge->tasksByDay() as $day => $tasks)
                        @php($row = $breakdown[(int) $day] ?? null)

                        <div class="overflow-hidden rounded-3xl ring-1 ring-line">
                            <div class="flex items-center justify-between bg-cream px-5 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}</p>

                                @if ($row)
                                    <span class="text-xs {{ $row['complete'] ? 'text-forest' : 'text-muted' }}">
                                        {{ fa_num($row['done']) }} از {{ fa_num($row['total']) }}
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2 p-3">
                                @foreach ($tasks as $task)
                                    @php($done = $participant->isTaskDone($task))

                                    <form method="POST" action="{{ route('panel.participants.tasks.toggle', [$participant, $task]) }}">
                                        @csrf

                                        <button type="submit" class="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-right transition {{ $done ? 'bg-leaf/10 hover:bg-leaf/10' : 'bg-cream hover:bg-line/60' }}">
                                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg text-xs font-bold {{ $done ? 'bg-leaf text-white' : 'bg-paper text-transparent ring-1 ring-line' }}">✓</span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm {{ $done ? 'text-muted/80 line-through' : 'font-medium text-ink' }}">{{ $task->title }}</span>
                                                @if ($task->description)
                                                    <span class="mt-1 block text-xs text-muted">{{ $task->description }}</span>
                                                @endif
                                            </span>

                                            @if ($done && ($times[(int) $task->id] ?? null))
                                                <span class="shrink-0 text-[10px] text-muted/80">{{ fa_datetime($times[(int) $task->id]) }}</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-cream p-6 text-center text-sm text-muted">کاری برای این چالش ثبت نشده است.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            {{-- حلقه پیشرفت --}}
            <section class="card text-center">
                <x-progress-ring :percent="$stats['progress']" :class="$style['stroke']" caption="پیشرفت" />

                <div class="mt-5 grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-2xl bg-cream px-3 py-3">
                        <p class="text-[11px] text-muted">شروع فعالیت</p>
                        <p class="mt-1 text-xs font-medium text-ink">{{ $stats['first_activity'] ? fa_date($stats['first_activity']) : '—' }}</p>
                    </div>
                    <div class="rounded-2xl bg-cream px-3 py-3">
                        <p class="text-[11px] text-muted">آخرین فعالیت</p>
                        <p class="mt-1 text-xs font-medium text-ink">{{ $stats['last_activity'] ? fa_date($stats['last_activity']) : '—' }}</p>
                    </div>
                </div>
            </section>

            {{-- آخرین تیک‌ها --}}
            <section class="card">
                <h2 class="text-sm font-semibold text-ink">آخرین تیک‌ها</h2>

                @if ($recent->isEmpty())
                    <p class="mt-4 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">هنوز کاری تیک نخورده است.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($recent as $completion)
                            <li class="flex items-start gap-3 rounded-2xl bg-cream px-4 py-3">
                                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg bg-leaf text-[10px] text-white">✓</span>
                                <span class="min-w-0">
                                    <span class="block text-xs text-ink">{{ $completion->task?->title }}</span>
                                    <span class="mt-0.5 block text-[10px] text-muted/80">
                                        {{ $completion->task?->day_number ? 'روز '.fa_num($completion->task->day_number).' · ' : '' }}{{ fa_datetime($completion->completed_at) }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- اطلاعات و لینک --}}
            <section class="card space-y-4">
                <h2 class="text-sm font-semibold text-ink">اطلاعات و لینک اختصاصی</h2>

                <form method="POST" action="{{ route('panel.participants.update', $participant) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="label" for="name">نام</label>
                        <input id="name" name="name" type="text" required value="{{ old('name', $participant->name) }}" class="input">
                    </div>

                    <div>
                        <label class="label" for="contact">راه ارتباطی</label>
                        <input id="contact" name="contact" type="text" value="{{ old('contact', $participant->contact) }}" class="input" placeholder="شماره تماس یا آیدی">
                    </div>

                    <div>
                        <label class="label" for="note">یادداشت مدیر</label>
                        <textarea id="note" name="note" rows="3" class="input resize-y" placeholder="یادداشتی که فقط مدیر می‌بیند...">{{ old('note', $participant->note) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">ذخیره</button>
                </form>

                <div class="border-t border-line pt-4">
                    <label class="label" for="participant-link">کد و لینک اختصاصی (بدون نیاز به ورود)</label>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2 rounded-2xl bg-cream px-4 py-3">
                            <span class="text-[11px] text-muted">کد:</span>
                            <span class="font-mono text-sm font-semibold tracking-[0.2em] text-ink" dir="ltr">{{ $participant->token }}</span>
                        </div>

                        <input id="participant-link" type="text" readonly value="{{ route('participant.show', $participant) }}" class="input text-[11px]" dir="ltr">

                        <div class="flex gap-2">
                            <button type="button" class="btn btn-soft flex-1" data-copy-target="#participant-link">کپی لینک</button>
                            <button type="button" class="btn btn-white" data-copy="{{ $participant->token }}">کپی کد</button>
                        </div>
                    </div>

                    <p class="hint">این کد و لینک را برای خودش بفرست؛ با همان داشبوردش را می‌بیند.</p>
                </div>
            </section>
        </aside>
    </div>
@endsection
