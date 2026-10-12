@extends('layouts.public')

@php
    $siteName = $site['site_name'] ?? config('panel.site_name');
    $activeChallenge = $challenges->first(fn ($challenge) => $challenge->status() === 'active') ?? $challenges->first();
@endphp

@section('title', $siteName.' — چالش‌های گروهی')

@section('content')
    {{-- قهرمان صفحه --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-24 end-0 size-72 rounded-full bg-jade/15 blur-3xl"></div>
        <div class="pointer-events-none absolute top-24 start-0 size-72 rounded-full bg-leaf/15 blur-3xl"></div>

        <div class="relative mx-auto max-w-6xl px-4 pt-16 pb-12 sm:px-6 lg:pt-24">
            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <span class="badge bg-paper text-brand-700 ring-brand-200">
                        <span class="size-1.5 rounded-full bg-brand-500"></span>
                        {{ $stats['challenges'] > 0 ? fa_num($stats['challenges']).' چالش در جریان' : 'چالش‌های گروهی' }}
                    </span>

                    <h1 class="mt-5 text-3xl leading-[1.35] sm:text-4xl sm:leading-[1.35] lg:text-5xl lg:leading-[1.3]">
                        با
                        <span class="bg-gradient-to-l from-forest to-leaf bg-clip-text text-transparent">{{ $siteName }}</span>
                        <br>
                        هر روز یک قدم کوچک، یک تغییر واقعی
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-8 text-ink/80">
                        {{ $site['site_tagline'] ?? config('panel.site_tagline') }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('challenges.index') }}" class="btn btn-primary px-5 py-3">دیدن چالش‌ها</a>
                        <a href="{{ route('participant.lookup') }}" class="btn btn-white px-5 py-3">کد اختصاصی من را دارم</a>
                    </div>

                    <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-line pt-6">
                        <div>
                            <dt class="text-xs text-muted">چالش‌های فعال</dt>
                            <dd class="mt-1 text-2xl font-bold text-ink">{{ fa_num($stats['challenges']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">شرکت‌کننده</dt>
                            <dd class="mt-1 text-2xl font-bold text-ink">{{ fa_num($stats['participants']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">کار انجام‌شده</dt>
                            <dd class="mt-1 text-2xl font-bold text-ink">{{ fa_num($stats['done']) }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- پیش‌نمایش داشبورد شرکت‌کننده --}}
                <div class="relative">
                    <div class="card animate-rise space-y-5 p-7">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="grid size-11 place-items-center rounded-2xl bg-gradient-to-tl from-forest to-leaf text-white">پ</span>
                                <div>
                                    <p class="text-sm font-semibold text-ink">داشبورد اختصاصی تو</p>
                                    <p class="text-xs text-muted">فقط با یک کد کوتاه، بدون ثبت‌نام</p>
                                </div>
                            </div>
                            <span class="badge bg-leaf/10 text-forest ring-leaf/30">فعال</span>
                        </div>

                        <div class="flex items-center gap-5">
                            <x-progress-ring :percent="68" :size="112" :stroke-width="10" caption="پیشرفت" />
                            <div class="space-y-3 text-sm">
                                <p class="flex items-center gap-2 text-ink/80"><span>🔥</span> ۵ روز پیوسته</p>
                                <p class="flex items-center gap-2 text-ink/80"><span>✅</span> ۱۷ کار انجام‌شده</p>
                                <p class="flex items-center gap-2 text-ink/80"><span>🎯</span> ۸ کار باقی‌مانده</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 text-sm">
                                <span class="grid size-6 place-items-center rounded-lg bg-leaf text-xs text-white">✓</span>
                                <span class="text-muted line-through">۲۰ دقیقه پیاده‌روی</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 text-sm">
                                <span class="grid size-6 place-items-center rounded-lg bg-line text-xs text-muted"></span>
                                <span class="text-ink">نوشتن سه جمله شکرگزاری</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 text-sm">
                                <span class="grid size-6 place-items-center rounded-lg bg-line text-xs text-muted"></span>
                                <span class="text-ink">۱۰ دقیقه تمرین تمرکز</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-xs text-brand-700">
                            <span>🔗</span>
                            <span class="font-medium">کد اختصاصی:</span>
                            <span class="tracking-[0.2em]" dir="ltr">k7m2p9qz</span>
                        </div>
                    </div>

                    <div class="pointer-events-none absolute -bottom-6 -start-6 hidden rounded-2xl bg-paper px-4 py-3 text-xs shadow-lg ring-1 ring-line sm:block">
                        پیشرفتت را همان روز می‌بینی ✨
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ورود سریع با کد اختصاصی --}}
    <section class="mx-auto max-w-6xl px-4 pb-4 sm:px-6">
        <div class="rounded-[2rem] bg-paper p-6 shadow-sm ring-1 ring-line sm:p-8">
            <div class="grid items-center gap-6 lg:grid-cols-[1.2fr_1fr]">
                <div>
                    <h2 class="text-xl">کد اختصاصی داری؟</h2>
                    <p class="mt-2 text-sm leading-7 text-ink/80">
                        کد کوتاهی که مسئول چالش برایت فرستاده را وارد کن تا داشبورد خودت باز شود؛ بدون ثبت‌نام، بدون رمز.
                    </p>
                </div>

                <form method="POST" action="{{ route('participant.lookup.attempt') }}" class="flex flex-wrap items-center gap-3 sm:flex-nowrap">
                    @csrf

                    <input name="code" type="text" required dir="ltr" class="input flex-1 text-center tracking-[0.3em]"
                           placeholder="k7m2p9qz" value="{{ old('code') }}" autocomplete="off" spellcheck="false">

                    <button type="submit" class="btn btn-primary shrink-0">باز کردن داشبورد</button>
                </form>
            </div>

            @error('code')
                <p class="mt-3 text-xs text-danger">{{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- چالش‌ها --}}
    <section id="challenges" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl">چالش‌های در جریان</h2>
                <p class="mt-2 text-sm text-muted">روی هر چالش بزن تا روزها، نکته‌ها و کارهایش را ببینی.</p>
            </div>

            <a href="{{ route('challenges.index') }}" class="btn btn-soft">همه چالش‌ها</a>
        </div>

        @if ($featured->isEmpty())
            <div class="card mt-8 text-center">
                <p class="text-3xl">🌱</p>
                <p class="mt-3 text-sm text-ink/80">به‌زودی اولین چالش اینجا منتشر می‌شود.</p>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $challenge)
                    <x-challenge-card :challenge="$challenge" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- چرا اینجا؟ --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="max-w-2xl">
            <h2 class="text-2xl">چرا با هم و در چالش؟</h2>
            <p class="mt-3 text-sm leading-8 text-ink/80">
                تغییرهای بزرگ از قدم‌های کوچک روزانه ساخته می‌شوند؛ وقتی در یک چالش گروهی همراه می‌شوی، ادامه دادن ساده‌تر است.
            </p>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['🗓️', 'مسیر روزانه روشن', 'هر روز بدان دقیقاً چه کاری باید انجام دهی؛ بدون سردرگمی و بدون برنامه‌ریزی پیچیده.'],
                ['📈', 'پیشرفت زنده', 'وقتی کاری را تیک می‌زنی، درصد پیشرفت و روزهای پیوسته‌ات بلافاصله به‌روز می‌شود.'],
                ['🙌', 'همراهی گروه', 'دیدن اینکه بقیه هم در همان مسیر قدم برمی‌دارند، انگیزه را زنده نگه می‌دارد.'],
                ['🔓', 'بدون ثبت‌نام', 'نه حساب کاربری، نه رمز، نه ایمیل؛ فقط یک کد کوتاه اختصاصی که همیشه در دستت است.'],
            ] as [$icon, $title, $text])
                <div class="card p-6">
                    <span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-lg">{{ $icon }}</span>
                    <h3 class="mt-4 text-base">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-7 text-ink/80">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- چطور کار می‌کند --}}
    <section id="how" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="rounded-[2rem] bg-paper p-8 shadow-sm ring-1 ring-line sm:p-12">
            <div class="max-w-2xl">
                <h2 class="text-2xl">چالش چطور اجرا می‌شود؟</h2>
                <p class="mt-3 text-sm leading-8 text-ink/80">
                    {{ $site['site_about'] ?? config('panel.site_about') }}
                </p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['۱', 'در فهرست چالش ثبت می‌شوی', 'نامت را به مسئول چالش می‌گویی و در فهرست شرکت‌کننده‌ها ثبت می‌شوی.'],
                    ['۲', 'یک کد کوتاه می‌گیری', 'برای هر نفر یک کد و لینک اختصاصی ساخته می‌شود؛ همان کد کلید داشبورد توست.'],
                    ['۳', 'هر روز تیک می‌زنی', 'کارهای روزت را انجام می‌دهی و در داشبورد خودت تیک می‌زنی؛ پیشرفتت همان لحظه ثبت می‌شود.'],
                ] as [$number, $title, $text])
                    <div class="rounded-3xl bg-cream/70 p-6">
                        <span class="grid size-10 place-items-center rounded-2xl bg-paper text-sm font-bold text-brand-700 shadow-sm ring-1 ring-line">{{ $number }}</span>
                        <h3 class="mt-4 text-base">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-7 text-ink/80">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            @if ($activeChallenge)
                <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-3xl {{ $activeChallenge->styleValue('bg') }} px-6 py-5">
                    <div class="flex items-center gap-4">
                        <span class="grid size-11 place-items-center rounded-2xl bg-paper text-lg shadow-sm">{{ $activeChallenge->emoji }}</span>
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ $activeChallenge->title }}</p>
                            <p class="mt-0.5 text-xs text-ink/80">
                                {{ $activeChallenge->dateRangeLabel() }}
                                @if ($activeChallenge->currentDayNumber())
                                    · امروز روز {{ fa_num($activeChallenge->currentDayNumber()) }} از {{ fa_num($activeChallenge->daysCount()) }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <a href="{{ $activeChallenge->publicUrl() }}" class="btn btn-white">دیدن این چالش</a>
                </div>
            @endif
        </div>
    </section>

    {{-- سوال‌های پرتکرار --}}
    <section id="faq" class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="text-center">
            <h2 class="text-2xl">سوال‌های پرتکرار</h2>
            <p class="mt-3 text-sm text-ink/80">اگر جواب سوالت را پیدا نکردی، از مسئول چالش بپرس.</p>
        </div>

        <div class="mt-8 space-y-3">
            @foreach ([
                ['برای شرکت در چالش باید ثبت‌نام کنم؟', 'نه. کافی است نامت در فهرست شرکت‌کننده‌های چالش ثبت شود؛ بعد از آن یک کد کوتاه اختصاصی می‌گیری و با همان داشبورد خودت را باز می‌کنی.'],
                ['کدم را گم کرده‌ام، چه کار کنم؟', 'از مسئول چالش بخواه لینک یا کد را دوباره برایت بفرستد یا برایت کد جدید بسازد. کد قبلی در آن حالت از کار می‌افتد.'],
                ['چطور کارهایم را تیک بزنم؟', 'در داشبورد خودت، فهرست کارهای هر روز را می‌بینی؛ روی هر کار بزن تا انجام‌شده شود. هر تیک بلافاصله در پیشرفتت حساب می‌شود.'],
                ['پیشرفت من را چه کسی می‌بیند؟', 'تنها مسئول چالش می‌تواند وضعیت شرکت‌کننده‌ها را ببیند. لینک تو هم مثل یک کلید شخصی است؛ آن را جایی منتشر نکن.'],
                ['اگر یک روز را از دست بدهم چه می‌شود؟', 'هیچ چیز از بین نمی‌رود. همان روز را می‌توانی بعداً هم کامل کنی و زنجیره‌ات را ادامه بدهی.'],
            ] as [$question, $answer])
                <details class="group overflow-hidden rounded-3xl bg-paper shadow-sm ring-1 ring-line">
                    <summary class="flex cursor-pointer items-center gap-3 px-6 py-5">
                        <span class="grid size-7 shrink-0 place-items-center rounded-xl bg-brand-50 text-xs font-bold text-brand-700">؟</span>
                        <span class="min-w-0 flex-1 text-sm font-medium text-ink">{{ $question }}</span>
                        <span class="text-muted/80 transition group-open:rotate-180">⌄</span>
                    </summary>

                    <div class="border-t border-line bg-cream/70 px-6 py-4 text-xs leading-7 text-ink/80">
                        {{ $answer }}
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- فراخوان پایانی --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-tl from-forest via-jade to-leaf p-10 text-white sm:p-14">
            <div class="pointer-events-none absolute -top-16 start-10 size-48 rounded-full bg-white/10 blur-2xl"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-2xl text-white">آماده‌ای امروز شروع کنی؟</h2>
                    <p class="mt-3 text-sm leading-8 text-white/80">
                        چالش‌ها را ببین، چالشی که به تو نزدیک است را انتخاب کن و همان امروز اولین قدم را بردار.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('challenges.index') }}" class="btn bg-paper px-5 py-3 text-brand-700 hover:bg-white/90">دیدن چالش‌ها</a>
                    <a href="{{ route('participant.lookup') }}" class="btn bg-white/10 px-5 py-3 text-white hover:bg-white/20">کد اختصاصی من</a>
                </div>
            </div>
        </div>
    </section>
@endsection
