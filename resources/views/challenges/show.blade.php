@extends('layouts.public')

@php($style = $challenge->style())

@section('title', $challenge->title.' — '.($site['site_name'] ?? config('panel.site_name')))
@section('description', $challenge->tagline ?: $challenge->title)

@section('content')
    <section class="mx-auto max-w-6xl px-4 pt-12 sm:px-6">
        <nav class="text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-700">خانه</a>
            <span class="mx-2">/</span>
            <a href="{{ route('challenges.index') }}" class="hover:text-brand-700">چالش‌ها</a>
            <span class="mx-2">/</span>
            <span class="text-slate-700">{{ $challenge->title }}</span>
        </nav>

        <div class="mt-6 overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-900/5">
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
                        <dd class="mt-1 font-medium">{{ fa_num($challenge->daysCount()) }} روز</dd>
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
            @if ($challenge->description)
                <div class="card">
                    <h2 class="text-lg">درباره این چالش</h2>
                    <p class="mt-4 text-sm leading-8 whitespace-pre-line text-slate-600">{{ $challenge->description }}</p>
                </div>
            @endif

            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg">روزها و کارها</h2>
                    <span class="text-xs text-slate-500">{{ fa_num($challenge->tasks_count) }} کار</span>
                </div>

                @forelse ($challenge->tasksByDay() as $day => $tasks)
                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-900/5">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-xl {{ $style['bg'] }} {{ $style['text'] }} text-xs font-bold">
                                    {{ $day > 0 ? fa_num($day) : '★' }}
                                </span>
                                <h3 class="text-sm font-semibold text-slate-900">
                                    {{ $day > 0 ? 'روز '.fa_num($day) : 'کارهای کلی' }}
                                </h3>
                            </div>

                            <span class="text-xs text-slate-500">{{ fa_num($tasks->count()) }} کار</span>
                        </div>

                        <ul class="divide-y divide-slate-100">
                            @foreach ($tasks as $task)
                                <li class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ $task->title }}</p>
                                    @if ($task->description)
                                        <p class="mt-1.5 text-xs leading-6 text-slate-500">{{ $task->description }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="card text-center text-sm text-slate-500">
                        کارهای این چالش هنوز ثبت نشده است.
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
            <div class="card text-center">
                <x-progress-ring :percent="$challenge->progressPercent()" :class="$style['stroke']" caption="پیشرفت کلی" />

                <p class="mt-5 text-xs leading-6 text-slate-500">
                    درصدی از همه کارها که شرکت‌کننده‌های این چالش تا این لحظه انجام داده‌اند.
                </p>
            </div>

            <div class="card">
                <h2 class="text-sm font-semibold text-slate-900">چطور در این چالش شرکت کنم؟</h2>
                <ol class="mt-4 space-y-4 text-xs leading-6 text-slate-600">
                    <li class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg {{ $style['bg'] }} {{ $style['text'] }} text-[11px] font-bold">۱</span>
                        <span>نامت را به مسئول چالش بگو تا در فهرست شرکت‌کننده‌ها ثبت شود.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg {{ $style['bg'] }} {{ $style['text'] }} text-[11px] font-bold">۲</span>
                        <span>یک لینک اختصاصی برایت ساخته می‌شود؛ همان لینک را ذخیره کن.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg {{ $style['bg'] }} {{ $style['text'] }} text-[11px] font-bold">۳</span>
                        <span>با آن لینک وارد داشبورد خودت می‌شوی، بدون رمز و ثبت‌نام، و کارهای هر روز را تیک می‌زنی.</span>
                    </li>
                </ol>
            </div>

            <div class="card bg-slate-900 text-white ring-slate-900">
                <p class="text-sm font-semibold">مدیر این چالش هستی؟</p>
                <p class="mt-2 text-xs leading-6 text-white/70">
                    از پنل مدیریت می‌توانی روزها و کارها را ویرایش کنی، نام شرکت‌کننده‌ها را اضافه کنی و لینک اختصاصی هر نفر را بگیری.
                </p>
                <a href="{{ route('panel.dashboard') }}" class="btn bg-white/10 mt-4 w-full text-white hover:bg-white/20">ورود به پنل مدیریت</a>
            </div>
        </aside>
    </section>
@endsection
