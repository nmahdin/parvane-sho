@extends('layouts.panel')

@php
    $style = $challenge->style();
    $progress = $participant->progressPercent();
@endphp

@section('title', $participant->name)
@section('page-title', 'شرکت‌کننده: '.$participant->name)
@section('page-subtitle', $challenge->title.' · '.fa_num($progress).'٪ پیشرفت')

@section('page-actions')
    <a href="{{ route('participant.show', $participant) }}" target="_blank" class="btn btn-soft btn-sm">دیدن داشبورد او ↗</a>
    <a href="{{ route('panel.challenges.show', $challenge) }}" class="btn btn-white btn-sm">بازگشت به چالش</a>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="grid size-14 place-items-center rounded-3xl bg-gradient-to-tl {{ $style['gradient'] }} text-xl font-bold text-white">
                            {{ mb_substr($participant->name, 0, 1) }}
                        </span>

                        <div>
                            <h2 class="text-lg">{{ $participant->name }}</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $challenge->emoji }} {{ $challenge->title }}
                                @if ($participant->contact)
                                    · <span dir="auto">{{ $participant->contact }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                آخرین بازدید: {{ $participant->last_seen_at ? fa_datetime($participant->last_seen_at) : 'هنوز باز نکرده' }}
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

                <div class="mt-6 grid gap-4 border-t border-slate-100 pt-6 sm:grid-cols-4">
                    <div>
                        <p class="text-xs text-slate-500">کار انجام‌شده</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($participant->completedCount()) }} از {{ fa_num($participant->totalTasks()) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">زنجیره فعلی</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($participant->currentStreak()) }} روز</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">بهترین زنجیره</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ fa_num($participant->bestStreak()) }} روز</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">پیشرفت</p>
                        <div class="mt-2"><x-progress-bar :percent="$progress" :class="$style['bar']" show-label /></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2 class="text-base">کارهای او</h2>
                <p class="mt-1 text-xs text-slate-500">
                    می‌توانی از اینجا هم به‌جای او تیک بزنی یا تیک را برداری (مثلاً وقتی خودش اطلاع داده است).
                </p>

                <div class="mt-5 space-y-5">
                    @forelse ($challenge->tasksByDay() as $day => $tasks)
                        <div class="overflow-hidden rounded-3xl ring-1 ring-slate-900/5">
                            <div class="bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}
                            </div>

                            <div class="space-y-2 p-3">
                                @foreach ($tasks as $task)
                                    @php($done = $participant->isTaskDone($task))

                                    <form method="POST" action="{{ route('panel.participants.tasks.toggle', [$participant, $task]) }}">
                                        @csrf

                                        <button type="submit" class="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-right transition {{ $done ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'bg-slate-50 hover:bg-slate-100' }}">
                                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg text-xs font-bold {{ $done ? 'bg-emerald-500 text-white' : 'bg-white text-transparent ring-1 ring-slate-300' }}">✓</span>

                                            <span class="min-w-0">
                                                <span class="block text-sm {{ $done ? 'text-slate-400 line-through' : 'font-medium text-slate-800' }}">{{ $task->title }}</span>
                                                @if ($task->description)
                                                    <span class="mt-1 block text-xs text-slate-500">{{ $task->description }}</span>
                                                @endif
                                            </span>
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

        <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
            <section class="card text-center">
                <x-progress-ring :percent="$progress" :class="$style['stroke']" caption="پیشرفت" />
            </section>

            <section class="card space-y-4">
                <h2 class="text-sm font-semibold text-slate-900">اطلاعات و لینک</h2>

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
                    <label class="label" for="participant-link">لینک اختصاصی (بدون نیاز به ورود)</label>

                    <div class="space-y-2">
                        <input id="participant-link" type="text" readonly value="{{ route('participant.show', $participant) }}" class="input text-[11px]" dir="ltr">
                        <button type="button" class="btn btn-soft w-full" data-copy-target="#participant-link">کپی لینک</button>
                    </div>

                    <p class="hint">این لینک را برای خودش بفرست؛ با همان لینک داشبوردش را می‌بیند.</p>
                </div>
            </section>
        </aside>
    </div>
@endsection
