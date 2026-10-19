@extends('layouts.panel')

@section('title', 'آمار بازدید')
@section('page-title', 'آمار بازدید')
@section('page-subtitle', 'بازدید روزانه و ماهانه، تفکیک هر چالش، پخش صوت‌ها، دانلودها و دیده‌شدن روزها')

@section('content')
    {{-- فیلترها: چالش، نوع بازدید و بازهٔ زمانی --}}
    <section class="card space-y-4">
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

            <div class="min-w-48 flex-1">
                <label class="label" for="analytics-type">فقط کدام نوع بازدید؟</label>
                <select id="analytics-type" name="type" class="input" onchange="this.form.submit()">
                    <option value="">همه انواع</option>
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <noscript><button type="submit" class="btn btn-white btn-sm">نمایش</button></noscript>

            @if ($selected || $type || $custom)
                <a href="{{ route('panel.analytics') }}" class="btn btn-ghost btn-sm">✕ حذف فیلترها</a>
            @endif
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-muted">بازهٔ زمانی:</span>

            @php
                $keep = array_filter([
                    'challenge' => $selected?->id,
                    'type' => $type,
                ], fn ($v) => filled($v));
            @endphp

            @foreach ($rangeOptions as $days => $label)
                <a href="{{ route('panel.analytics', array_merge($keep, ['range' => $days])) }}"
                   class="rounded-2xl px-3.5 py-1.5 text-xs font-medium transition {{ ! $custom && $range === $days ? 'bg-jade text-white' : 'bg-cream text-muted hover:bg-line/50 hover:text-forest' }}">{{ $label }}</a>
            @endforeach

            <span class="rounded-2xl px-3.5 py-1.5 text-xs font-medium {{ $custom ? 'bg-jade text-white' : 'bg-cream text-muted' }}">دلخواه</span>
        </div>

        <form method="GET" action="{{ route('panel.analytics') }}" class="flex flex-wrap items-end gap-3">
            @if ($selected)<input type="hidden" name="challenge" value="{{ $selected->id }}">@endif
            @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif

            <div>
                <label class="label" for="analytics-from">از تاریخ</label>
                <input id="analytics-from" name="from" type="text" data-jalali-picker value="{{ request('from') }}" class="input w-40 pe-11" placeholder="۱۴۰۵/۰۷/۰۱" inputmode="numeric">
            </div>

            <div>
                <label class="label" for="analytics-to">تا تاریخ</label>
                <input id="analytics-to" name="to" type="text" data-jalali-picker value="{{ request('to') }}" class="input w-40 pe-11" placeholder="۱۴۰۵/۰۷/۳۰" inputmode="numeric">
            </div>

            <button type="submit" class="btn btn-white btn-sm">اعمال بازه</button>
        </form>
    </section>

    {{-- کارت‌های خلاصه --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="بازدید در بازهٔ انتخابی" :value="fa_num($totals['range'])" :hint="'از '.fa_date($from).' تا '.fa_date($to)" icon="🎯"
                     tip="جمع همهٔ بازدیدهای همین بازهٔ زمانی با فیلترهای فعلی — نمودارهای پایین صفحه هم دقیقاً همین بازه را نشان می‌دهند." />
        <x-stat-card label="بازدید امروز" :value="fa_num($totals['today'])" :hint="fa_date(now(), true)" icon="📅"
                     tip="از ابتدای امروز تا همین لحظه، مستقل از بازهٔ انتخابی." />
        <x-stat-card label="۷ روز اخیر" :value="fa_num($totals['week'])" hint="یک هفتهٔ گذشته" icon="🗓️" tone="sky"
                     tip="هفت روز کامل اخیر شامل امروز." />
        <x-stat-card label="۳۰ روز اخیر" :value="fa_num($totals['month'])" hint="یک ماه گذشته" icon="📈" tone="emerald"
                     tip="سی روز کامل اخیر شامل امروز." />
        <x-stat-card label="کل بازدیدها" :value="fa_num($totals['all'])" :hint="fa_num($totals['dashboard']).' ورود داشبورد'" icon="👁️" tone="amber"
                     tip="همهٔ بازدیدهای ثبت‌شده از ابتدای فعالیت سایت، با فیلتر چالش انتخابی." />
        <x-stat-card label="دیده‌شدن روزها" :value="fa_num($totals['day_views'])" hint="باز شدن کارت روزها" icon="📖" tone="rose"
                     tip="هر بار که شرکت‌کننده کارتی را در سایت باز می‌کند یک بازدید روز ثبت می‌شود." />
        <x-stat-card label="پخش فایل صوتی" :value="fa_num($fileTotals['plays'])" hint="پخش‌کنندهٔ خود سایت" icon="🎧" />
        <x-stat-card label="دانلود فایل روزها" :value="fa_num($fileTotals['downloads'])" hint="فایل‌های غیرصوتی روزها" icon="⬇️" tone="sky" />
        <x-stat-card label="دانلود ضمیمه‌ها" :value="fa_num($fileTotals['attachments'])" hint="فایل‌های ضمیمهٔ چالش" icon="📎" tone="emerald" />
    </div>

    {{-- نمودارها (chart.js — داده از $chartsJson) --}}
    <section class="card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base">نمودار بازدید — بازهٔ انتخابی</h2>
                <p class="mt-1 text-xs text-muted">خط روند هر نوع بازدید از {{ fa_date($from) }} تا {{ fa_date($to) }}{{ $weeklyBuckets ? ' — چون بازه بلند است، نقطه‌ها هفتگی جمع شده‌اند' : '' }}{{ $isSuper && ! $selected ? ' (شامل صفحه اصلی)' : '' }}.</p>
            </div>
        </div>

        <div class="mt-4" style="height: 19rem">
            <canvas id="chart-daily" role="img" aria-label="نمودار خطی بازدید روزانه ۳۰ روز اخیر"></canvas>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="text-sm font-semibold text-ink">جمع ماهانه (شمسی)</h3>
                <p class="mt-0.5 text-[11px] text-hint">در بازهٔ انتخابی، تا دوازده ماه.</p>
                <div class="mt-3" style="height: 13rem">
                    <canvas id="chart-monthly" role="img" aria-label="نمودار ستونی جمع ماهانه"></canvas>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-ink">سهم هر نوع بازدید (بازهٔ انتخابی)</h3>
                <p class="mt-0.5 text-[11px] text-hint">صفحه چالش، باز شدن کارت روز، داشبورد و صفحه اصلی.</p>
                <div class="mt-3" style="height: 13rem">
                    <canvas id="chart-types" role="img" aria-label="نمودار دونات سهم انواع بازدید"></canvas>
                </div>
            </div>
        </div>

        @if (! $selected && $perChallenge->isNotEmpty())
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-ink">پربازدیدترین چالش‌ها (۳۰ روز اخیر)</h3>
                <div class="mt-3" style="height: 15rem">
                    <canvas id="chart-challenges" role="img" aria-label="نمودار ستونی بازدید چالش‌ها"></canvas>
                </div>
            </div>
        @endif

        <script id="analytics-data" type="application/json">{!! $chartsJson !!}</script>
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
