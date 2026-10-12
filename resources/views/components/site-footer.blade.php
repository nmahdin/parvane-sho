@php($siteName = $site['site_name'] ?? config('panel.site_name'))

<footer class="no-print mt-20 border-t border-line bg-paper">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
        <div>
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-2xl bg-gradient-to-tl from-forest to-leaf text-lg text-white">🦋</span>
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

    <div class="border-t border-line py-5 text-center text-xs text-muted">
        {{ $siteName }} — امروز {{ fa_date(now(), true) }}
    </div>
</footer>
