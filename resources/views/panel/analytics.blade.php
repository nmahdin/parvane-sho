@extends('layouts.panel')

@section('title', 'آمار بازدید')
@section('page-title', 'آمار بازدید')
@section('page-subtitle', 'بازدید روزانه و ماهانه، تفکیک هر چالش، پخش صوت‌ها، دانلودها و دیده‌شدن روزها')

@section('content')
    {{-- فیلتر چالش --}}
    <section class="card">
        <form method="GET" action="{{ route('panel.analytics') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-56 flex-1">
                <label class="label" for="analytics-challenge">آمار کدام چالش؟</label>
                <select id="analytics-challenge" name="challenge" class="input" onchange="this.form.submit()">
                    <option value="">همه چالش‌ها{{ $isSuper ? '' : ' (مربوط به من)' }}</option>
                    @foreach ($challenges as $challenge)
                        <option value="{{ $challenge->id }}" @selected($selected && $selected->id === $challenge->id)>
                            {{ $challenge->title }}{{ $challenge->is_published ? '' : ' (پنهان)' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <noscript><button type="submit" class="btn btn-white btn-sm">نمایش</button></noscript>

            @if ($selected)
                <a href="{{ route('panel.analytics') }}" class="btn btn-ghost btn-sm">✕ حذف فیلتر</a>
            @endif
        </form>
    </section>

    {{-- کارت‌های خلاصه --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="بازدید امروز" :value="fa_num($totals['today'])" :hint="fa_date(now(), true)" icon="📅" />
        <x-stat-card label="۷ روز اخیر" :value="fa_num($totals['week'])" hint="یک هفتهٔ گذشته" icon="🗓️" tone="sky" />
        <x-stat-card label="۳۰ روز اخیر" :value="fa_num($totals['month'])" hint="یک ماه گذشته" icon="📈" tone="emerald" />
        <x-stat-card label="کل بازدیدها" :value="fa_num($totals['all'])" :hint="fa_num($totals['dashboard']).' ورود داشبورد'" icon="👁️" tone="amber" />
        <x-stat-card label="دیده‌شدن روزها" :value="fa_num($totals['day_views'])" hint="باز شدن کارت روزها" icon="📖" tone="rose" />
        <x-stat-card label="پخش فایل صوتی" :value="fa_num($fileTotals['plays'])" hint="پخش‌کنندهٔ خود سایت" icon="🎧" />
        <x-stat-card label="دانلود فایل روزها" :value="fa_num($fileTotals['downloads'])" hint="فایل‌های غیرصوتی روزها" icon="⬇️" tone="sky" />
        <x-stat-card label="دانلود ضمیمه‌ها" :value="fa_num($fileTotals['attachments'])" hint="فایل‌های ضمیمهٔ چالش" icon="📎" tone="emerald" />
    </div>

    {{-- نمودار روزانه ۳۰ روز اخیر --}}
    <section class="card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base">بازدید روزانه — ۳۰ روز اخیر</h2>
                <p class="mt-1 text-xs text-muted">صفحه چالش + باز شدن کارت روزها + ورود به داشبورد شرکت‌کننده{{ $isSuper && ! $selected ? ' + صفحه اصلی' : '' }}.</p>
            </div>
        </div>

        <div class="mt-4 space-y-1.5">
            @foreach (array_reverse($daily, true) as $date => $row)
                @php
                    [$jy, $jm, $jd] = \App\Support\Jalali::fromGregorian(
                        (int) substr($date, 0, 4),
                        (int) substr($date, 5, 2),
                        (int) substr($date, 8, 2),
                    );
                @endphp

                <div class="flex items-center gap-3 text-xs">
                    <span class="w-24 shrink-0 text-muted">{{ fa_num($jd) }} {{ \App\Support\Jalali::MONTHS[$jm] }}</span>

                    <div class="h-3 flex-1 overflow-hidden rounded-full bg-cream ring-1 ring-line/60">
                        <div class="h-3 rounded-full bg-jade/80" style="width: {{ max($row['total'] > 0 ? 3 : 0, round($row['total'] / $dailyMax * 100)) }}%"></div>
                    </div>

                    <span class="w-10 shrink-0 text-left font-semibold text-ink">{{ fa_num($row['total']) }}</span>

                    <span class="hidden w-56 shrink-0 text-[11px] text-hint sm:block">
                        چالش {{ fa_num($row['challenge']) }} · روز {{ fa_num($row['day']) }} · داشبورد {{ fa_num($row['dashboard']) }}@if ($isSuper && ! $selected) · خانه {{ fa_num($row['home']) }} @endif
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- جمع ماهانه --}}
    <section class="card">
        <h2 class="text-base">جمع ماهانه (شمسی)</h2>
        <p class="mt-1 text-xs text-muted">شش ماه اخیر — جمع همهٔ بازدیدها.</p>

        <div class="mt-4 space-y-2">
            @forelse ($monthly as $month)
                <div class="flex items-center gap-3 text-xs">
                    <span class="w-28 shrink-0 text-muted">{{ $month['label'] }}</span>

                    <div class="h-3.5 flex-1 overflow-hidden rounded-full bg-cream ring-1 ring-line/60">
                        <div class="h-3.5 rounded-full bg-gold/70" style="width: {{ max(3, round($month['total'] / $monthlyMax * 100)) }}%"></div>
                    </div>

                    <span class="w-14 shrink-0 text-left font-semibold text-ink">{{ fa_num($month['total']) }}</span>
                </div>
            @empty
                <p class="rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">هنوز بازدیدی ثبت نشده است.</p>
            @endforelse
        </div>
    </section>

    {{-- تفکیک روزبه‌روز برای چالش انتخاب‌شده --}}
    @if ($selected && $perDay)
        <section class="card">
            <h2 class="text-base">روزبه‌روز — {{ $selected->title }}</h2>
            <p class="mt-1 text-xs text-muted">دیده‌شدن کارت هر روز، پخش صوت‌ها و دانلود فایل‌های همان روز.</p>

            <div class="table-wrap mt-4">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>روز</th>
                            <th>بازدید کارت روز</th>
                            <th>پخش صوت</th>
                            <th>دانلود فایل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perDay as $row)
                            <tr>
                                <td data-label="روز">روز {{ fa_num($row['day']) }}</td>
                                <td data-label="بازدید">{{ fa_num($row['views']) }}</td>
                                <td data-label="پخش">{{ fa_num($row['plays']) }}</td>
                                <td data-label="دانلود">{{ fa_num($row['downloads']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- جدول چالش‌ها --}}
    <section class="card">
        <h2 class="text-base">{{ $isSuper ? 'تفکیک همهٔ چالش‌ها' : 'تفکیک چالش‌های من' }}</h2>
        <p class="mt-1 text-xs text-muted">برای دیدن آمار روزبه‌روز یک چالش، روی «نمایش آمار» بزن.</p>

        <div class="table-wrap mt-4">
            <table class="table table-cards">
                <thead>
                    <tr>
                        <th>چالش</th>
                        <th>کل بازدید</th>
                        <th>امروز</th>
                        <th>۳۰ روز</th>
                        <th>دیدن روزها</th>
                        <th>پخش صوت</th>
                        <th>دانلود</th>
                        <th>شرکت‌کننده</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perChallenge as $row)
                        <tr>
                            <td data-label="چالش">
                                <span class="font-medium text-ink">{{ $row['title'] }}</span>
                                @unless ($row['published'])
                                    <span class="badge bg-gold/15 text-ink ring-gold/50">پنهان</span>
                                @endunless
                            </td>
                            <td data-label="کل">{{ fa_num($row['all']) }}</td>
                            <td data-label="امروز">{{ fa_num($row['today']) }}</td>
                            <td data-label="۳۰ روز">{{ fa_num($row['month']) }}</td>
                            <td data-label="دیدن روزها">{{ fa_num($row['days']) }}</td>
                            <td data-label="پخش">{{ fa_num($row['plays']) }}</td>
                            <td data-label="دانلود">{{ fa_num($row['downloads']) }}</td>
                            <td data-label="شرکت‌کننده">{{ fa_num($row['participants']) }}</td>
                            <td data-label="">
                                <a href="{{ route('panel.analytics', ['challenge' => $row['id']]) }}" class="btn btn-white btn-sm">نمایش آمار</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
