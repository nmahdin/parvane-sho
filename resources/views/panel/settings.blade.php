@extends('layouts.panel')

@section('title', 'تنظیمات')
@section('page-title', 'تنظیمات')
@section('page-subtitle', 'حساب من، رمز عبور و تنظیمات سایت')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card space-y-5">
            <div>
                <h2 class="text-base">حساب من</h2>
                <p class="mt-1 text-xs leading-6 text-muted">
                    شما با این نام کاربری وارد می‌شوید و رمز عبورت را همین‌جا می‌توانی عوض کنی.
                </p>
            </div>

            <div class="flex items-center gap-3 rounded-2xl bg-cream px-4 py-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-paper text-sm font-bold text-ink/80 ring-1 ring-line">
                    {{ mb_substr($admin->name, 0, 1) }}
                </span>

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink">{{ $admin->name }}</p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-muted">
                        <span dir="ltr">{{ $admin->username }}</span>
                        <span class="badge {{ $admin->roleClasses() }}">{{ $admin->roleLabel() }}</span>
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('panel.settings.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="label" for="current_password">رمز فعلی</label>
                    <input id="current_password" name="current_password" type="password" required dir="ltr" class="input text-center" autocomplete="current-password">
                    @error('current_password')
                        <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label" for="new_password">رمز جدید</label>
                    <input id="new_password" name="password" type="password" required dir="ltr" class="input text-center" autocomplete="new-password">
                    <p class="hint">حداقل ۶ کاراکتر.</p>
                    @error('password')
                        <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label" for="password_confirmation">تکرار رمز جدید</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required dir="ltr" class="input text-center" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary">تغییر رمز من</button>
            </form>
        </section>
        @if ($isSuper)
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
        @endif

        <div class="space-y-6">
            <section class="card space-y-4">
                <div>
                    <h2 class="text-base">مدیران چالش‌ها</h2>
                    <p class="mt-1 text-xs leading-6 text-muted">
                        چند مدیر می‌توانند در این پنل کار کنند؛ هر مدیر با نام کاربری و رمز خودش وارد می‌شود و
                        فقط چالش‌ها و شرکت‌کننده‌های خودش را می‌بیند. مدیر اصلی همه چالش‌ها و همه مدیران را می‌بیند.
                    </p>
                </div>

                @if ($isSuper)
                    <a href="{{ route('panel.admins.index') }}" class="btn btn-primary btn-sm">مدیریت مدیران</a>
                    <p class="text-[11px] leading-6 text-hint">
                        از آن صفحه می‌توانی مدیر جدید بسازی، رمزش را عوض کنی یا حسابش را غیرفعال کنی.
                    </p>
                @else
                    <div class="rounded-2xl bg-cream px-4 py-3 text-xs leading-6 text-ink/80 ring-1 ring-line">
                        برای ساخت مدیر جدید یا تغییر دسترسی‌ها با مدیر اصلی سایت تماس بگیر.
                    </div>
                @endif
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
