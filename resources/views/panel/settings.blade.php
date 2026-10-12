@extends('layouts.panel')

@section('title', 'تنظیمات')
@section('page-title', 'تنظیمات')
@section('page-subtitle', 'نام سایت، متن‌های صفحه اصلی و رمز ورود پنل')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card space-y-5">
            <div>
                <h2 class="text-base">تنظیمات سایت</h2>
                <p class="mt-1 text-xs text-muted">این متن‌ها در صفحه اصلی و پابرگ سایت نمایش داده می‌شوند.</p>
            </div>

            <form method="POST" action="{{ route('panel.settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="label" for="site_name">نام سایت</label>
                    <input id="site_name" name="site_name" type="text" required value="{{ old('site_name', $site_name) }}" class="input">
                </div>

                <div>
                    <label class="label" for="site_tagline">شعار / توضیح کوتاه</label>
                    <textarea id="site_tagline" name="site_tagline" rows="3" class="input resize-y">{{ old('site_tagline', $site_tagline) }}</textarea>
                </div>

                <div>
                    <label class="label" for="site_about">درباره سایت</label>
                    <textarea id="site_about" name="site_about" rows="5" class="input resize-y">{{ old('site_about', $site_about) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
            </form>
        </section>

        <div class="space-y-6">
            <section class="card space-y-5">
                <div>
                    <h2 class="text-base">رمز ورود پنل</h2>
                    <p class="mt-1 text-xs leading-6 text-muted">
                        ورود به پنل فقط با رمز عبور انجام می‌شود؛ نام کاربری و ایمیل لازم نیست.
                    </p>
                </div>

                @if ($useDefaultPassword)
                    <div class="rounded-2xl bg-gold/15 px-4 py-3 text-xs leading-6 text-ink/80 ring-1 ring-gold/40">
                        در حال حاضر از رمز پیش‌فرض فایل <code class="rounded bg-white/60 px-1.5 py-0.5" dir="ltr">.env</code> استفاده می‌شود
                        (<code class="rounded bg-white/60 px-1.5 py-0.5" dir="ltr">PANEL_PASSWORD</code>).
                        پیشنهاد می‌شود از همین‌جا رمز را عوض کنی تا از این پس رمز هش‌شده در دیتابیس ذخیره شود.
                    </div>
                @endif

                <form method="POST" action="{{ route('panel.settings.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="label" for="current_password">رمز فعلی</label>
                        <input id="current_password" name="current_password" type="password" required class="input" autocomplete="current-password">
                        @error('current_password')
                            <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label" for="new_password">رمز جدید</label>
                        <input id="new_password" name="password" type="password" required class="input" autocomplete="new-password">
                        <p class="hint">حداقل ۶ کاراکتر.</p>
                    </div>

                    <div>
                        <label class="label" for="password_confirmation">تکرار رمز جدید</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="input" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">تغییر رمز پنل</button>
                </form>
            </section>

            <section class="card">
                <h2 class="text-base">لینک شرکت‌کننده‌ها</h2>
                <p class="mt-2 text-xs leading-7 text-ink/80">
                    شرکت‌کننده‌ها نیازی به رمز ندارند. برای هر نفر در صفحه «مدیریت چالش» یک لینک اختصاصی ساخته می‌شود
                    (مثل <code class="rounded bg-line/60 px-1.5 py-0.5 text-[10px]" dir="ltr">/p/xxxxxxxxxxxxxxxx</code>) که
                    با باز کردن آن، داشبورد همان نفر نمایش داده می‌شود و می‌تواند کارهایش را تیک بزند.
                </p>
                <p class="mt-3 text-xs leading-7 text-ink/80">
                    اگر لینکی جایی عمومی شده باشد، از همان صفحه روی «لینک جدید» بزن؛ لینک قبلی از کار می‌افتد.
                </p>
            </section>
        </div>
    </div>
@endsection
