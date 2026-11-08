@extends('layouts.public')

@section('title', 'ورود با کد اختصاصی — '.($site['site_name'] ?? config('panel.site_name')))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="mx-auto max-w-xl px-4 pt-16 pb-20 sm:px-6">
        <div class="text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-3xl bg-gradient-to-tl from-forest to-leaf text-2xl text-white shadow-sm">🔗</span>
            <h1 class="mt-5 text-2xl">ورود با کد اختصاصی</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-ink/80">
                کدی که مسئول چالش برایت فرستاده را وارد کن تا داشبورد چالش خودت باز شود.
                نیازی به ثبت‌نام، نام کاربری یا رمز عبور نیست.
            </p>
        </div>

        <div class="mt-8">
            <x-flash />
        </div>

        <form method="POST" action="{{ route('participant.lookup.attempt') }}" class="card mt-6 space-y-5">
            @csrf

            <div>
                <label for="code" class="label">کد اختصاصی</label>

                <input id="code"
                       name="code"
                       type="text"
                       required
                       autofocus
                       dir="ltr"
                       value="{{ old('code') }}"
                       class="input text-center text-lg tracking-[0.35em]"
                       placeholder="k7m2p9qz"
                       autocomplete="off"
                       spellcheck="false">

                <p class="hint">می‌توانی کل لینکی که گرفته‌ای را هم اینجا بچسبانی.</p>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">باز کردن داشبورد من</button>
        </form>

        <div class="mt-6 rounded-3xl bg-paper p-5 text-xs leading-6 text-muted ring-1 ring-line">
            <p class="font-medium text-ink">کد را پیدا نمی‌کنی؟</p>
            <p class="mt-2">
                کد اختصاصی هر نفر را مسئول چالش می‌سازد و برای خودش می‌فرستد. اگر کدت را گم کردی، از مسئول چالش بخواه
                لینک را دوباره برایت بفرستد.
            </p>
            <a href="{{ route('challenges.index') }}" class="link mt-3 inline-block">دیدن چالش‌ها</a>
        </div>
    </section>
@endsection
