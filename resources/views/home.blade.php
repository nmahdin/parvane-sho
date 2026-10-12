@extends('layouts.public')

@section('title', ($site['site_name'] ?? config('panel.site_name')).' — چالش‌های گروهی')

@section('content')
    {{-- قهرمان صفحه --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-24 end-0 size-72 rounded-full bg-brand-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute top-24 start-0 size-72 rounded-full bg-fuchsia-200/40 blur-3xl"></div>

        <div class="relative mx-auto max-w-6xl px-4 pt-16 pb-12 sm:px-6 lg:pt-24">
            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <span class="badge bg-white text-brand-700 ring-brand-200">
                        <span class="size-1.5 rounded-full bg-brand-500"></span>
                        پلتفرم چالش‌های گروهی
                    </span>

                    <h1 class="mt-5 text-3xl leading-[1.35] sm:text-4xl sm:leading-[1.35] lg:text-5xl lg:leading-[1.3]">
                        با
                        <span class="bg-gradient-to-l from-brand-600 to-fuchsia-500 bg-clip-text text-transparent">
                            {{ $site['site_name'] ?? config('panel.site_name') }}
                        </span>
                        <br>
                        هر روز یک قدم به هدف نزدیک‌تر شو
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-600">
                        {{ $site['site_tagline'] ?? config('panel.site_tagline') }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('challenges.index') }}" class="btn btn-primary px-5 py-3">دیدن چالش‌ها</a>
                        <a href="#how" class="btn btn-white px-5 py-3">چالش چطور اجرا می‌شود؟</a>
                    </div>

                    <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-slate-200 pt-6">
                        <div>
                            <dt class="text-xs text-slate-500">چالش‌های فعال</dt>
                            <dd class="mt-1 text-2xl font-bold text-slate-900">{{ fa_num($stats['challenges']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">شرکت‌کننده</dt>
                            <dd class="mt-1 text-2xl font-bold text-slate-900">{{ fa_num($stats['participants']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">کار انجام‌شده</dt>
                            <dd class="mt-1 text-2xl font-bold text-slate-900">{{ fa_num($stats['done']) }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- کارت پیش‌نمایش داشبورد شرکت‌کننده --}}
                <div class="relative">
                    <div class="card animate-rise space-y-5 p-7">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-tl from-brand-600 to-fuchsia-500 text-white">پ</span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">داشبورد اختصاصی شما</p>
                                    <p class="text-xs text-slate-500">بدون نام کاربری، فقط با یک لینک</p>
                                </div>
                            </div>
                            <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">فعال</span>
                        </div>

                        <div class="flex items-center gap-5">
                            <x-progress-ring :percent="68" :size="112" :stroke-width="10" caption="پیشرفت" />
                            <div class="space-y-3 text-sm">
                                <p class="flex items-center gap-2 text-slate-600"><span>🔥</span> ۵ روز پیوسته</p>
                                <p class="flex items-center gap-2 text-slate-600"><span>✅</span> ۱۷ کار انجام‌شده</p>
                                <p class="flex items-center gap-2 text-slate-600"><span>🎯</span> ۸ کار باقی‌مانده</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                <span class="grid size-6 place-items-center rounded-lg bg-emerald-500 text-xs text-white">✓</span>
                                <span class="text-slate-500 line-through">۲۰ دقیقه پیاده‌روی</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                <span class="grid size-6 place-items-center rounded-lg bg-slate-200 text-xs text-slate-500"></span>
                                <span class="text-slate-700">نوشتن سه جمله شکرگزاری</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                <span class="grid size-6 place-items-center rounded-lg bg-slate-200 text-xs text-slate-500"></span>
                                <span class="text-slate-700">۱۰ دقیقه تمرین تمرکز</span>
                            </div>
                        </div>
                    </div>

                    <div class="pointer-events-none absolute -bottom-6 -start-6 hidden rounded-2xl bg-white px-4 py-3 text-xs shadow-lg ring-1 ring-slate-900/5 sm:block">
                        لینک اختصاصی شما آماده است 🔗
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- چالش‌ها --}}
    <section id="challenges" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl">چالش‌های در جریان</h2>
                <p class="mt-2 text-sm text-slate-500">روی هر چالش بزن تا روزها و کارهایش را ببینی.</p>
            </div>

            <a href="{{ route('challenges.index') }}" class="btn btn-soft">همه چالش‌ها</a>
        </div>

        @if ($featured->isEmpty())
            <div class="card mt-8 text-center">
                <p class="text-3xl">🌱</p>
                <p class="mt-3 text-sm text-slate-600">هنوز چالشی منتشر نشده است. از پنل مدیریت اولین چالش را بسازید.</p>
                <a href="{{ route('panel.dashboard') }}" class="btn btn-primary mt-5">ورود به پنل مدیریت</a>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $challenge)
                    <x-challenge-card :challenge="$challenge" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- چطور کار می‌کند --}}
    <section id="how" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-900/5 sm:p-12">
            <div class="max-w-2xl">
                <h2 class="text-2xl">چالش چطور اجرا می‌شود؟</h2>
                <p class="mt-3 text-sm leading-8 text-slate-600">
                    {{ $site['site_about'] ?? config('panel.site_about') }}
                </p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['۱', 'چالش را بساز', 'عنوان، توضیح و تاریخ شروع و پایان چالش را در پنل وارد کن و کارهای هر روز را بنویس.'],
                    ['۲', 'نام‌ها را وارد کن', 'فهرست شرکت‌کننده‌ها را خط‌به‌خط اضافه کن؛ برای هر نفر خودکار یک لینک اختصاصی ساخته می‌شود.'],
                    ['۳', 'لینک را بفرست', 'هر نفر لینک خودش را باز می‌کند، بدون رمز و ثبت‌نام؛ کارهای روزانه‌اش را تیک می‌زند و پیشرفتش را می‌بیند.'],
                ] as [$number, $title, $text])
                    <div class="rounded-3xl bg-slate-50/80 p-6">
                        <span class="grid size-10 place-items-center rounded-2xl bg-white text-sm font-bold text-brand-700 shadow-sm ring-1 ring-slate-900/5">{{ $number }}</span>
                        <h3 class="mt-4 text-base">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- فراخوان پایانی --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-tl from-brand-700 via-brand-600 to-fuchsia-600 p-10 text-white sm:p-14">
            <div class="pointer-events-none absolute -top-16 start-10 size-48 rounded-full bg-white/10 blur-2xl"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-2xl text-white">می‌خواهی چالشی راه بیندازی؟</h2>
                    <p class="mt-3 text-sm leading-8 text-white/80">
                        ورود به پنل مدیریت فقط با یک رمز انجام می‌شود؛ نه نام کاربری، نه ایمیل. چالش بساز، شرکت‌کننده‌ها را اضافه کن و لینک‌ها را برایشان بفرست.
                    </p>
                </div>

                <a href="{{ route('panel.dashboard') }}" class="btn bg-white px-5 py-3 text-brand-700 hover:bg-white/90">ورود به پنل مدیریت</a>
            </div>
        </div>
    </section>
@endsection
