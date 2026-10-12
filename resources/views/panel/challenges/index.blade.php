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
                <p class="text-3xl">🦋</p>
                <p class="mt-3 text-sm text-ink/80">هنوز چالشی ساخته نشده است.</p>
                <a href="{{ route('panel.challenges.create') }}" class="btn btn-primary mt-5">ساخت اولین چالش</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
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
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-cream">{{ $challenge->emoji }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ route('panel.challenges.show', $challenge) }}" class="block max-w-[16rem] truncate font-medium text-ink hover:text-brand-700">
                                                {{ $challenge->title }}
                                            </a>
                                            <span class="text-[11px] text-muted/80" dir="ltr">/{{ $challenge->slug }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="flex flex-col items-start gap-1.5">
                                        <span class="badge {{ $challenge->statusClasses() }}">{{ $challenge->statusLabel() }}</span>
                                        <span class="text-[11px] {{ $challenge->is_published ? 'text-forest' : 'text-muted/80' }}">
                                            {{ $challenge->is_published ? 'در سایت نمایش داده می‌شود' : 'پنهان از سایت' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="text-xs text-muted">{{ $challenge->dateRangeLabel() }}</td>

                                <td class="text-xs text-ink/80">{{ fa_num($challenge->daysCount()) }} روز · {{ fa_num($challenge->tasksCount()) }} کار · {{ fa_num($challenge->questionsCount()) }} پرسش</td>

                                <td class="text-sm">
                                    <a href="{{ route('panel.challenges.show', $challenge) }}#participants" class="text-ink hover:text-brand-700">
                                        {{ fa_num($challenge->participants_count) }} نفر
                                    </a>
                                </td>

                                <td><x-progress-bar :percent="$challenge->progressPercent()" :class="$challenge->styleValue('bar')" show-label /></td>

                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('panel.challenges.show', $challenge) }}" class="btn btn-ghost btn-sm">مدیریت</a>
                                        <a href="{{ route('panel.challenges.edit', $challenge) }}" class="btn btn-ghost btn-sm">ویرایش</a>

                                        <form method="POST" action="{{ route('panel.challenges.publish', $challenge) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-ghost btn-sm">{{ $challenge->is_published ? 'پنهان کن' : 'منتشر کن' }}</button>
                                        </form>

                                        <form method="POST" action="{{ route('panel.challenges.destroy', $challenge) }}" data-confirm="چالش «{{ $challenge->title }}» و همه شرکت‌کننده‌ها و پیشرفت‌هایش حذف شود؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                        </form>
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
