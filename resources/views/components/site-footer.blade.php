@php($siteName = $site['site_name'] ?? config('panel.site_name'))

<footer class="no-print mt-20 border-t border-line bg-paper">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
        <div>
            <div class="flex items-center gap-3">
                <img src="{{ \App\Models\Setting::get('site_logo') ? route('branding', 'logo') : asset('favicon.svg') }}" alt="{{ $site['site_name'] ?? config('panel.site_name') }}" class="size-10 rounded-2xl bg-white p-1.5 shadow-sm" loading="lazy" decoding="async">
                <span class="text-base font-semibold text-ink">{{ $siteName }}</span>
            </div>
            <p class="mt-4 max-w-sm text-sm leading-7 text-muted">
                {{ $site['site_tagline'] ?? config('panel.site_tagline') }}
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink">دسترسی سریع</h3>
            <ul class="mt-4 space-y-2 text-sm text-ink/80">
                <li><a href="{{ route('challenges.index') }}" class="hover:text-brand-700">فهرست چالش‌ها</a></li>
                <li><a href="{{ route('home') }}#how" class="hover:text-brand-700">چالش چطور اجرا می‌شود؟</a></li>
                <li><a href="{{ route('home') }}#faq" class="hover:text-brand-700">سوال‌های پرتکرار</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink">شرکت در چالش</h3>
            <ul class="mt-4 space-y-2 text-sm text-ink/80">
                <li><a href="{{ route('participant.lookup') }}" class="hover:text-brand-700">ورود با کد اختصاصی</a></li>
                <li>برای دیدن پیشرفتت فقط کافی است کد یا لینکی که مسئول چالش داده را باز کنی؛ ثبت‌نام و رمزی لازم نیست.</li>
            </ul>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3 border-t border-line px-4 py-5 text-xs text-muted">
        <span>{{ $siteName }} — امروز {{ fa_date(now(), true) }}</span>
        <a href="{{ route('panel.login') }}" class="btn btn-white btn-sm">ورود به پنل مدیریت چالش</a>
    </div>
</footer>
