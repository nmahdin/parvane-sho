@extends('layouts.panel')

@section('title', 'داشبورد')
@section('page-title', 'داشبورد')
@section('page-subtitle', 'یک نگاه کلی به چالش‌ها، شرکت‌کننده‌ها و پیشرفت امروز')

@section('page-actions')
    <a href="{{ route('panel.challenges.create') }}" class="btn btn-primary btn-sm">+ چالش جدید</a>
@endsection

@section('content')
    {{-- آمار کلی --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="چالش‌ها" :value="fa_num($stats['challenges'])" :hint="fa_num($stats['published']).' چالش منتشرشده'" icon="🦋" />
        <x-stat-card label="شرکت‌کننده‌ها" :value="fa_num($stats['participants'])" hint="در همه چالش‌ها" icon="👥" tone="sky" />
        <x-stat-card label="تیک‌های امروز" :value="fa_num($stats['today'])" hint="{{ fa_date(now(), true) }}" icon="✅" tone="emerald" />
        <x-stat-card label="کارهای انجام‌شده" :value="fa_num($stats['done'])" hint="در همه چالش‌ها" icon="🎯" tone="amber" />
        <x-stat-card label="پاسخ پرسش‌ها" :value="fa_num($stats['answers'])" hint="نوشته‌شده توسط شرکت‌کننده‌ها" icon="❓" tone="rose" />
    </div>

    {{-- چالش‌ها --}}
    <section class="card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base">چالش‌ها</h2>
                <p class="mt-1 text-xs text-muted">برای مدیریت روزها، کارها و شرکت‌کننده‌ها روی «مدیریت» بزن.</p>
            </div>

            <a href="{{ route('panel.challenges.index') }}" class="btn btn-white btn-sm">همه چالش‌ها</a>
        </div>

        @if ($challenges->isEmpty())
            <div class="mt-6 rounded-2xl bg-cream p-8 text-center">
                <p class="text-2xl">🌱</p>
                <p class="mt-3 text-sm text-ink/80">هنوز چالشی نساخته‌اید.</p>
                <a href="{{ route('panel.challenges.create') }}" class="btn btn-primary mt-4">ساخت اولین چالش</a>
            </div>
        @else
            <div class="table-wrap mt-5">
                <table class="table">
                    <thead>
                        <tr>
                            <th>چالش</th>
                            <th>وضعیت</th>
                            <th>بازه</th>
                            <th>شرکت‌کننده</th>
                            <th class="w-48">پیشرفت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($challenges as $challenge)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-cream">{{ $challenge->emoji }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ route('panel.challenges.show', $challenge) }}" class="block truncate font-medium text-ink hover:text-brand-700">
                                                {{ $challenge->title }}
                                            </a>
                                            <span class="text-[11px] text-muted">{{ fa_num($challenge->tasksCount()) }} کار · {{ fa_num($challenge->questionsCount()) }} پرسش · {{ fa_num($challenge->daysCount()) }} روز</span>
                                        </div>
                                    </div>
                                </td>

                                <td><span class="badge {{ $challenge->statusClasses() }}">{{ $challenge->statusLabel() }}</span></td>

                                <td class="text-xs text-muted">{{ $challenge->dateRangeLabel() }}</td>

                                <td class="text-sm">{{ fa_num($challenge->participants_count) }} نفر</td>

                                <td>
                                    <x-progress-bar :percent="$challenge->progressPercent()" :class="$challenge->styleValue('bar')" show-label />
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('panel.challenges.show', $challenge) }}" class="btn btn-ghost btn-sm">مدیریت</a>
                                        <a href="{{ $challenge->publicUrl() }}" target="_blank" class="btn btn-ghost btn-sm">سایت ↗</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- نام شرکت‌کننده‌ها به تفکیک چالش --}}
    <section class="card">
        <h2 class="text-base">شرکت‌کننده‌ها</h2>
        <p class="mt-1 text-xs text-muted">
            نام هر کسی که در فهرست چالش‌ها ثبت شده اینجاست؛ با کلیک روی هر نام، داشبورد اختصاصی او باز می‌شود.
        </p>

        <div class="mt-6 space-y-6">
            @forelse ($challenges as $challenge)
                @php($members = $participants->where('challenge_id', $challenge->id))

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-ink">
                            {{ $challenge->emoji }} {{ $challenge->title }}
                            <span class="ms-2 text-xs font-normal text-muted">{{ fa_num($members->count()) }} نفر</span>
                        </h3>

                        <a href="{{ route('panel.challenges.show', $challenge) }}#participants" class="text-xs text-brand-700 hover:underline">افزودن و مدیریت لینک‌ها</a>
                    </div>

                    @if ($members->isEmpty())
                        <p class="mt-3 rounded-2xl bg-cream px-4 py-3 text-xs text-muted">هنوز نامی برای این چالش ثبت نشده است.</p>
                    @else
                        <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($members->sortByDesc('completed_count') as $participant)
                                <a href="{{ route('panel.participants.show', $participant) }}"
                                   class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3 transition hover:bg-line/60">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-xs font-bold text-ink/80 ring-1 ring-line">
                                        {{ mb_substr($participant->name, 0, 1) }}
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm text-ink">{{ $participant->name }}</span>
                                        <span class="mt-1 block">
                                            <x-progress-bar :percent="$participant->progressPercent()" :class="$challenge->styleValue('bar')" height="h-1.5" />
                                        </span>
                                    </span>

                                    <span class="shrink-0 text-[11px] text-muted">{{ fa_num($participant->completed_count) }} کار</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="rounded-2xl bg-cream px-4 py-6 text-center text-sm text-muted">با ساخت اولین چالش، فهرست شرکت‌کننده‌ها اینجا نمایش داده می‌شود.</p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- فعال‌ترین‌ها --}}
        <section class="card">
            <h2 class="text-base">فعال‌ترین شرکت‌کننده‌ها</h2>

            @if ($topParticipants->isEmpty())
                <p class="mt-4 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">هنوز هیچ کاری تیک نخورده است.</p>
            @else
                <ol class="mt-5 space-y-3">
                    @foreach ($topParticipants as $index => $participant)
                        <li class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3">
                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-paper text-xs font-bold text-muted ring-1 ring-line">{{ fa_num($index + 1) }}</span>

                            <span class="min-w-0 flex-1">
                                <a href="{{ route('panel.participants.show', $participant) }}" class="block truncate text-sm text-ink hover:text-brand-700">
                                    {{ $participant->name }}
                                </a>
                                <span class="text-[11px] text-muted">{{ $participant->challenge->title }}</span>
                            </span>

                            <span class="badge bg-leaf/10 text-forest ring-leaf/30">{{ fa_num($participant->completed_count) }} کار</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- آخرین فعالیت‌ها --}}
        <section class="card">
            <h2 class="text-base">آخرین تیک‌ها</h2>

            @if ($recent->isEmpty())
                <p class="mt-4 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">فعالیتی ثبت نشده است.</p>
            @else
                <ul class="mt-5 space-y-3">
                    @foreach ($recent as $completion)
                        <li class="flex items-start gap-3 rounded-2xl bg-cream px-4 py-3">
                            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-lg bg-leaf text-[11px] text-white">✓</span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm text-ink">
                                    <span class="font-medium">{{ $completion->participant->name }}</span>
                                    —
                                    {{ $completion->task->title }}
                                </span>
                                <span class="mt-0.5 block text-[11px] text-muted">
                                    {{ $completion->participant->challenge->title }} · {{ fa_datetime($completion->completed_at) }}
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- آخرین پاسخ‌های شرکت‌کننده‌ها --}}
    <section class="card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base">آخرین پاسخ‌های شرکت‌کننده‌ها</h2>
                <p class="mt-1 text-xs text-muted">پاسخ‌هایی که شرکت‌کننده‌ها برای پرسش‌های چالش‌ها نوشته‌اند؛ برای خواندن همه، سراغ صفحه همان چالش برو.</p>
            </div>

            <span class="badge {{ $stats['answers'] > 0 ? 'bg-jade/10 text-jade ring-jade/30' : 'bg-cream text-muted ring-line' }}">
                {{ fa_num($stats['answers']) }} پاسخ
            </span>
        </div>

        @if ($recentAnswers->isEmpty())
            <p class="mt-5 rounded-2xl bg-cream px-4 py-6 text-center text-xs text-muted">هنوز کسی به پرسشی پاسخ نداده است.</p>
        @else
            <div class="mt-5 grid gap-3 lg:grid-cols-2">
                @foreach ($recentAnswers as $answer)
                    <div class="rounded-2xl bg-cream p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <a href="{{ route('panel.participants.show', $answer->participant) }}" class="text-sm font-semibold text-forest hover:text-brand-700">
                                {{ $answer->participant->name }}
                            </a>

                            <span class="shrink-0 text-[11px] text-muted/80">{{ fa_datetime($answer->updated_at) }}</span>
                        </div>

                        <p class="mt-1.5 text-xs font-medium text-ink">{{ $answer->question->question ?? 'پرسش حذف‌شده' }}</p>
                        <p class="mt-1.5 line-clamp-3 whitespace-pre-line text-xs leading-6 text-ink/80">{{ $answer->body }}</p>

                        @if ($answer->question?->challenge)
                            <a href="{{ route('panel.challenges.show', $answer->question->challenge) }}#tasks" class="mt-2 inline-block text-[11px] text-brand-700 hover:underline">
                                {{ $answer->question->challenge->title }} — دیدن پرسش‌ها و پاسخ‌ها
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
