@extends('layouts.public')

@section('title', 'چالش‌ها — '.($site['site_name'] ?? config('panel.site_name')))
@section('description', 'فهرست چالش‌های در جریان و به پایان رسیده')

@section('content')
    <section class="mx-auto max-w-6xl px-4 pt-14 pb-10 sm:px-6">
        <nav class="text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-brand-700">خانه</a>
            <span class="mx-2">/</span>
            <span class="text-ink">چالش‌ها</span>
        </nav>

        <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">چالش‌ها</h1>
                <p class="mt-3 max-w-2xl text-sm leading-8 text-ink/80">
                    هر چالش از چند روز تشکیل شده و هر روز یک یا چند کار دارد. شرکت‌کننده‌ها با لینک اختصاصی خود پیشرفتشان را می‌بینند.
                </p>
            </div>

        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        @if ($challenges->isEmpty())
            <div class="card text-center">
                <p class="text-3xl">🌷</p>
                <p class="mt-3 text-sm text-ink/80">در این لحظه چالش منتشرشده‌ای وجود ندارد.</p>
                <a href="{{ route('home') }}" class="btn btn-soft mt-5">بازگشت به خانه</a>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($challenges as $challenge)
                    <x-challenge-card :challenge="$challenge" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
