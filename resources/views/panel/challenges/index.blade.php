@extends('layouts.panel')

@section('title', 'چالش‌ها')
@section('page-title', 'چالش‌ها')
@section('page-subtitle', 'ساخت، ویرایش و انتشار چالش‌ها')

@section('page-actions')
    <a href="{{ route('panel.challenges.create') }}" class="btn btn-primary btn-sm">+ چالش جدید</a>
@endsection

@section('content')
    <section class="card">
        @if ($challenges->isEmpty())
            <div class="py-8 text-center">
                <img src="{{ asset('favicon.svg') }}" alt="" class="mx-auto size-14" loading="lazy" decoding="async">
                <p class="mt-3 text-sm text-ink/80">هنوز چالشی ساخته نشده است.</p>
                <a href="{{ route('panel.challenges.create') }}" class="btn btn-primary mt-5">ساخت اولین چالش</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>چالش</th>
                            <th>وضعیت</th>
                            <th>بازه</th>
                            <th>روز / کار</th>
                            <th>شرکت‌کننده</th>
                            <th class="w-44">پیشرفت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($challenges as $challenge)
                            <tr>
                                <td data-label="">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-cream">{{ $challenge->emoji }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ route('panel.challenges.show', $challenge) }}" class="block max-w-[16rem] truncate font-medium text-ink hover:text-brand-700">
                                                {{ $challenge->title }}
                                            </a>
                                            <span class="text-[11px] text-hint" dir="ltr">/{{ $challenge->slug }}</span>

                                            @if ($admin?->isSuper() && $challenge->admin)
                                                <span class="mt-1 block text-[11px] text-muted">
                                                    مدیر: {{ $challenge->admin->name }}
                                                    @if ($challenge->admin->isSuper())
                                                        <span class="badge bg-gold/15 text-ink ring-gold/50">مدیر اصلی</span>
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td data-label="وضعیت">
                                    <div class="flex flex-col items-start gap-1.5">
                                        <span class="badge {{ $challenge->statusClasses() }}">{{ $challenge->statusLabel() }}</span>
                                        <span class="text-[11px] {{ $challenge->is_published ? 'text-forest' : 'text-hint' }}">
                                            {{ $challenge->is_published ? 'در سایت نمایش داده می‌شود' : 'پنهان از سایت' }}
                                        </span>
                                    </div>
                                </td>

                                <td data-label="بازه" class="text-xs text-muted">{{ $challenge->dateRangeLabel() }}</td>

                                <td data-label="روز / کار" class="text-xs text-ink/80">{{ fa_num($challenge->totalDays()) }} روز · {{ fa_num($challenge->tasksCount()) }} کار · {{ fa_num($challenge->questionsCount()) }} پرسش</td>

                                <td data-label="شرکت‌کننده" class="text-sm">
                                    <a href="{{ route('panel.challenges.show', $challenge) }}#participants" class="text-ink hover:text-brand-700">
                                        {{ fa_num($challenge->participants_count) }} نفر
                                    </a>
                                </td>

                                <td data-label="پیشرفت"><x-progress-bar :percent="$challenge->progressPercent()" :class="$challenge->styleValue('bar')" show-label /></td>

                                <td data-label="">
                                    <div class="flex items-center justify-end gap-1" data-action-menu data-challenge-actions="{{ $challenge->id }}">
                                        <a href="{{ route('panel.challenges.show', $challenge) }}" class="btn btn-ghost btn-sm">مدیریت</a>

                                        <details class="action-menu">
                                            <summary class="btn btn-ghost btn-sm" aria-label="عملیات چالش {{ $challenge->title }}">عملیات ▾</summary>

                                            <div class="action-menu-panel">
                                                <a href="{{ route('panel.challenges.show', $challenge) }}">📋 مدیریت روزها و محتوا</a>
                                                <a href="{{ route('panel.challenges.edit', $challenge) }}">✏️ ویرایش چالش</a>
                                                <a href="{{ route('challenges.show', $challenge) }}" target="_blank" rel="noopener">🌐 دیدن صفحه عمومی</a>

                                                <form method="POST" action="{{ route('panel.challenges.publish', $challenge) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit">{{ $challenge->is_published ? '🙈 پنهان کردن' : '📢 منتشر کردن' }}</button>
                                                </form>

                                                <form method="POST" action="{{ route('panel.challenges.destroy', $challenge) }}" data-confirm="چالش «{{ $challenge->title }}» و همه شرکت‌کننده‌ها و پیشرفت‌هایش حذف شود؟">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="is-danger">🗑 حذف چالش</button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
