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

                    <p class="mt-1.5 text-xs text-slate-500">
                        <a href="{{ route('panel.challenges.show', $challenge) }}" class="hover:text-brand-700">{{ $challenge->emoji }} {{ $challenge->title }}</a>
                        @if ($participant->contact)
                            · <span dir="auto">{{ $participant->contact }}</span>
                        @endif
                    </p>

                    <p class="mt-1 text-[11px] text-slate-400">
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

        <div class="mt-6 grid gap-4 border-t border-slate-100 pt-6 sm:grid-cols-3 xl:grid-cols-6">
            <div>
                <p class="text-xs text-slate-500">کار انجام‌شده</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($stats['completed']) }} از {{ fa_num($stats['total']) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">روزهای کامل</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($stats['completed_days']) }} از {{ fa_num($stats['days_count']) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">روزهای از دست رفته</p>
                <p class="mt-1 text-lg font-bold {{ $stats['missed_days'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ fa_num($stats['missed_days']) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">زنجیره فعلی</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($stats['streak']) }} روز</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">بهترین زنجیره</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($stats['best_streak']) }} روز</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">پیشرفت</p>
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
                        <p class="mt-1 text-xs text-slate-500">هر خانه یک روز از چالش است؛ عددها نشان می‌دهد چند کار از آن روز انجام شده.</p>
                    </div>

                    @if ($stats['current_day'])
                        <span class="badge bg-brand-50 text-brand-700 ring-brand-200">امروز: روز {{ fa_num($stats['current_day']) }}</span>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($breakdown as $day => $row)
                        @php
                            $tone = $row['complete']
                                ? 'bg-emerald-50 ring-emerald-200'
                                : ($row['done'] > 0 ? 'bg-amber-50 ring-amber-200' : ($row['is_past'] ? 'bg-rose-50 ring-rose-200' : 'bg-slate-50 ring-slate-200'));
                        @endphp

                        <div class="rounded-2xl p-3 ring-1 {{ $tone }} {{ $row['is_today'] ? 'ring-2 ring-brand-300' : '' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-700">{{ $day > 0 ? 'روز '.fa_num($day) : 'کلی' }}</span>

                                @if ($row['complete'])
                                    <span class="text-xs text-emerald-600">✓ کامل</span>
                                @elseif ($row['done'] > 0)
                                    <span class="text-xs text-amber-600">{{ fa_num($row['done']) }}/{{ fa_num($row['total']) }}</span>
                                @elseif ($row['is_past'])
                                    <span class="text-xs text-rose-500">انجام نشده</span>
                                @else
                                    <span class="text-xs text-slate-400">در انتظار</span>
                                @endif
                            </div>

                            <div class="mt-2">
                                <div class="h-1.5 overflow-hidden rounded-full bg-white/70">
                                    <div class="h-full rounded-full {{ $row['complete'] ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $row['total'] ? round($row['done'] / $row['total'] * 100) : 0 }}%"></div>
                                </div>
                            </div>

                            @if ($row['is_today'])
                                <p class="mt-1.5 text-[10px] text-brand-700">امروز</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- نمودار فعالیت --}}
            <section class="card">
                <h2 class="text-base">فعالیت ۱۴ روز اخیر</h2>
                <p class="mt-1 text-xs text-slate-500">هر ستون یک روز است؛ ارتفاع نشان می‌دهد آن روز چند کار انجام داده است.</p>

                <div class="mt-6 flex items-end justify-between gap-1.5">
                    @foreach ($activity as $date => $count)
                        @php
                            $height = $count === 0 ? 6 : max(12, (int) round($count / $maxActivity * 100));
                            $isToday = \Illuminate\Support\Carbon::parse($date)->isToday();
                        @endphp

                        <div class="group flex flex-1 flex-col items-center gap-2" title="{{ fa_date($date, true) }} — {{ fa_num($count) }} کار">
                            <div class="flex w-full items-end justify-center" style="height: 120px">
                                <div class="w-full rounded-t-lg transition-all {{ $count === 0 ? 'bg-slate-200' : 'bg-brand-500 group-hover:bg-brand-600' }}"
                                     style="height: {{ $height }}%"></div>
                            </div>

                            <span class="text-[10px] {{ $isToday ? 'font-bold text-brand-700' : 'text-slate-400' }}">{{ fa_num($count) }}</span>
                            <span class="text-[9px] text-slate-400">{{ fa_num(\Illuminate\Support\Carbon::parse($date)->format('j')) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- تیک‌زدن کارها --}}
            <section class="card">
                <h2 class="text-base">کارهای او</h2>
                <p class="mt-1 text-xs text-slate-500">
                    می‌توانی از اینجا هم به‌جای او تیک بزنی یا تیک را برداری (مثلاً وقتی خودش اطلاع داده است).
                </p>

                <div class="mt-5 space-y-5">
                    @forelse ($challenge->tasksByDay() as $day => $tasks)
                        @php($row = $breakdown[(int) $day] ?? null)

                        <div class="overflow-hidden rounded-3xl ring-1 ring-slate-900/5">
                            <div class="flex items-center justify-between bg-slate-50 px-5 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}</p>

                                @if ($row)
                                    <span class="text-xs {{ $row['complete'] ? 'text-emerald-600' : 'text-slate-500' }}">
                                        {{ fa_num($row['done']) }} از {{ fa_num($row['total']) }}
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2 p-3">
                                @foreach ($tasks as $task)
                                    @php($done = $participant->isTaskDone($task))

                                    <form method="POST" action="{{ route('panel.participants.tasks.toggle', [$participant, $task]) }}">
                                        @csrf

                                        <button type="submit" class="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-right transition {{ $done ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'bg-slate-50 hover:bg-slate-100' }}">
                                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg text-xs font-bold {{ $done ? 'bg-emerald-500 text-white' : 'bg-white text-transparent ring-1 ring-slate-300' }}">✓</span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm {{ $done ? 'text-slate-400 line-through' : 'font-medium text-slate-800' }}">{{ $task->title }}</span>
                                                @if ($task->description)
                                                    <span class="mt-1 block text-xs text-slate-500">{{ $task->description }}</span>
                                                @endif
                                            </span>

                                            @if ($done && ($times[(int) $task->id] ?? null))
                                                <span class="shrink-0 text-[10px] text-slate-400">{{ fa_datetime($times[(int) $task->id]) }}</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">کاری برای این چالش ثبت نشده است.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            {{-- حلقه پیشرفت --}}
            <section class="card text-center">
                <x-progress-ring :percent="$stats['progress']" :class="$style['stroke']" caption="پیشرفت" />

                <div class="mt-5 grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-2xl bg-slate-50 px-3 py-3">
                        <p class="text-[11px] text-slate-500">شروع فعالیت</p>
                        <p class="mt-1 text-xs font-medium text-slate-800">{{ $stats['first_activity'] ? fa_date($stats['first_activity']) : '—' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-3 py-3">
                        <p class="text-[11px] text-slate-500">آخرین فعالیت</p>
                        <p class="mt-1 text-xs font-medium text-slate-800">{{ $stats['last_activity'] ? fa_date($stats['last_activity']) : '—' }}</p>
                    </div>
                </div>
            </section>

            {{-- آخرین تیک‌ها --}}
            <section class="card">
                <h2 class="text-sm font-semibold text-slate-900">آخرین تیک‌ها</h2>

                @if ($recent->isEmpty())
                    <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-6 text-center text-xs text-slate-500">هنوز کاری تیک نخورده است.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($recent as $completion)
                            <li class="flex items-start gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg bg-emerald-500 text-[10px] text-white">✓</span>
                                <span class="min-w-0">
                                    <span class="block text-xs text-slate-700">{{ $completion->task?->title }}</span>
                                    <span class="mt-0.5 block text-[10px] text-slate-400">
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
                <h2 class="text-sm font-semibold text-slate-900">اطلاعات و لینک اختصاصی</h2>

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

                <div class="border-t border-slate-100 pt-4">
                    <label class="label" for="participant-link">کد و لینک اختصاصی (بدون نیاز به ورود)</label>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="text-[11px] text-slate-500">کد:</span>
                            <span class="font-mono text-sm font-semibold tracking-[0.2em] text-slate-800" dir="ltr">{{ $participant->token }}</span>
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
