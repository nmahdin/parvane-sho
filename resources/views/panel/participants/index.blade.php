@extends('layouts.panel')

@section('title', 'شرکت‌کننده‌ها')
@section('page-title', 'شرکت‌کننده‌ها')
@section('page-subtitle', 'نام همه کسانی که در فهرست چالش‌ها ثبت شده‌اند')

@section('content')
    <section class="card">
        <form method="GET" action="{{ route('panel.participants.index') }}" class="grid gap-4 sm:grid-cols-[1fr_14rem_auto]">
            <div>
                <label class="label" for="q">جست‌وجوی نام</label>
                <input id="q" name="q" type="text" value="{{ $search }}" class="input" placeholder="بخشی از نام...">
            </div>

            <div>
                <label class="label" for="challenge">چالش</label>
                <select id="challenge" name="challenge" class="input">
                    <option value="">همه چالش‌ها</option>
                    @foreach ($challenges as $challenge)
                        <option value="{{ $challenge->slug }}" @selected((string) $challengeFilter === $challenge->slug)>{{ $challenge->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary">فیلتر</button>
                <a href="{{ route('panel.participants.index') }}" class="btn btn-white">پاک کردن</a>
            </div>
        </form>
    </section>

    <section class="card">
        @if ($participants->isEmpty())
            <div class="py-8 text-center">
                <p class="text-2xl">👥</p>
                <p class="mt-3 text-sm text-slate-600">شرکت‌کننده‌ای با این مشخصات پیدا نشد.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>نام</th>
                            <th>چالش</th>
                            <th class="w-40">پیشرفت</th>
                            <th>آخرین بازدید</th>
                            <th class="min-w-[20rem]">لینک اختصاصی</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-slate-50 text-xs font-bold text-slate-600">{{ mb_substr($participant->name, 0, 1) }}</span>
                                        <div>
                                            <a href="{{ route('panel.participants.show', $participant) }}" class="block font-medium text-slate-900 hover:text-brand-700">{{ $participant->name }}</a>
                                            @if ($participant->contact)
                                                <span class="text-[11px] text-slate-400">{{ $participant->contact }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <a href="{{ route('panel.challenges.show', $participant->challenge) }}" class="text-xs text-slate-600 hover:text-brand-700">
                                        {{ $participant->challenge->emoji }} {{ $participant->challenge->title }}
                                    </a>
                                </td>

                                <td>
                                    <x-progress-bar :percent="$participant->progressPercent()" :class="$participant->challenge->styleValue('bar')" show-label />
                                </td>

                                <td class="text-xs text-slate-500">
                                    {{ $participant->last_seen_at ? fa_datetime($participant->last_seen_at) : 'هنوز باز نکرده' }}
                                </td>

                                <td>
                                    <div class="flex items-center gap-2">
                                        <input id="row-link-{{ $participant->id }}" type="text" readonly value="{{ route('participant.show', $participant) }}" class="input py-2 text-[11px]" dir="ltr">
                                        <button type="button" class="btn btn-soft btn-sm shrink-0" data-copy-target="#row-link-{{ $participant->id }}">کپی</button>
                                    </div>
                                </td>

                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('panel.participants.show', $participant) }}" class="btn btn-ghost btn-sm">جزئیات</a>
                                        <a href="{{ route('participant.show', $participant) }}" target="_blank" class="btn btn-ghost btn-sm">داشبورد ↗</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $participants->links() }}
            </div>
        @endif
    </section>
@endsection
