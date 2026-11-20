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

                    <label class="flex cursor-pointer items-center gap-2 rounded-2xl bg-cream/70 px-4 py-3 text-xs text-ink/80">
                        <input type="hidden" name="show_admin_name" value="0">
                        <input type="checkbox" name="show_admin_name" value="1" @checked(old('show_admin_name', \App\Models\Setting::showAdminName()))>
                        نام برگزارکنندهٔ چالش در صفحهٔ عمومی چالش نشان داده شود
                    </label>

                    <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
                </form>
            </section>

            {{-- رنگ‌های سایت --}}
            <section class="card space-y-5">
                <div>
                    <h2 class="text-base">رنگ‌های سایت</h2>
                    <p class="mt-1 text-xs text-muted">
                        همین کدها تمام رنگ‌های سایت و پنل را می‌سازند؛ تغییر بده و ذخیره کن، همان لحظه همه‌جا اعمال می‌شود.
                    </p>
                </div>

                @error('colors.*')
                    <p class="rounded-2xl bg-danger-soft px-4 py-3 text-xs text-danger">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('panel.settings.theme') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $themeLabels = [
                                'jade' => 'سبز اصلی (دکمه‌ها و لینک‌ها)',
                                'forest' => 'سبز تیره (متن و عنوان‌ها)',
                                'leaf' => 'برگ (لهجهٔ روشن)',
                                'gold' => 'کرم طلایی (نکته‌ها)',
                                'cream' => 'کرم داخلی (سطح‌ها)',
                                'shell' => 'پس‌زمینه پنل',
                                'canvas' => 'پس‌زمینه سایت',
                                'line' => 'خطوط و ورودی‌ها',
                            ];
                        @endphp

                        @foreach ($themeDefaults as $key => $default)
                            <label class="flex items-center gap-3 rounded-2xl bg-cream/70 px-3 py-2.5">
                                <input type="color" name="colors[{{ $key }}]" value="{{ old('colors.'.$key, $themeColors[$key]) }}"
                                       class="size-10 shrink-0 cursor-pointer rounded-xl border-0 bg-transparent p-0">
                                <span class="min-w-0">
                                    <span class="block text-xs leading-5 font-semibold text-ink">{{ $themeLabels[$key] ?? $key }}</span>
                                    <span class="block text-[11px] text-muted" dir="ltr">{{ old('colors.'.$key, $themeColors[$key]) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn btn-primary">ذخیره رنگ‌ها</button>
                        <button type="submit" name="reset" value="1" class="btn btn-white"
                                onclick="return confirm('رنگ‌ها به حالت پیش‌فرض برگردانده شود؟');">بازنشانی به پیش‌فرض</button>
                    </div>
                </form>
            </section>

            {{-- لوگو و فاوآیکون --}}
            <section class="card space-y-5">
                <div>
                    <h2 class="text-base">لوگو و فاوآیکون</h2>
                    <p class="mt-1 text-xs text-muted">لوگو در سربرگ سایت، پنل و صفحه ورود نشان داده می‌شود؛ فاوآیکون همان آیکون تب مرورگر است.</p>
                </div>

                <form method="POST" action="{{ route('panel.settings.branding') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-cream/70 p-4">
                            <div class="flex items-center gap-4">
                                <img src="{{ $branding['logo'] ? route('branding', 'logo').'?v='.urlencode($branding['logo']) : asset('favicon.svg') }}" alt="لوگوی فعلی" class="size-14 rounded-2xl bg-white p-1.5 shadow-sm">
                                <div>
                                    <p class="text-xs font-semibold text-ink">لوگو</p>
                                    <p class="mt-0.5 text-[11px] text-muted">{{ $branding['logo'] ? 'فایل فعلی: '.$branding['logo'] : 'پیش‌فرض (پروانه)' }}</p>
                                </div>
                            </div>

                            <label class="label mt-3" for="branding_logo">فایل تازه (png، jpg، webp یا svg — حداکثر ۲ مگابایت)</label>
                            <input id="branding_logo" type="file" name="logo" accept="image/*" class="input">

                            @if ($branding['logo'])
                                <label class="mt-2 flex items-center gap-2 text-[11px] text-muted">
                                    <input type="checkbox" name="remove_logo" value="1" class="size-4 rounded">
                                    حذف لوگوی سفارشی و بازگشت به پروانهٔ پیش‌فرض
                                </label>
                            @endif
                        </div>

                        <div class="rounded-2xl bg-cream/70 p-4">
                            <div class="flex items-center gap-4">
                                <img src="{{ $branding['favicon'] ? route('branding', 'favicon').'?v='.urlencode($branding['favicon']) : asset('favicon.svg') }}" alt="فاوآیکون فعلی" class="size-14 rounded-2xl bg-white p-1.5 shadow-sm">
                                <div>
                                    <p class="text-xs font-semibold text-ink">فاوآیکون</p>
                                    <p class="mt-0.5 text-[11px] text-muted">{{ $branding['favicon'] ? 'فایل فعلی: '.$branding['favicon'] : 'پیش‌فرض (پروانه)' }}</p>
                                </div>
                            </div>

                            <label class="label mt-3" for="branding_favicon">فایل تازه (svg، png یا ico — حداکثر ۵۱۲ کیلوبایت)</label>
                            <input id="branding_favicon" type="file" name="favicon" accept=".svg,.png,.ico,.webp,.jpg" class="input">

                            @if ($branding['favicon'])
                                <label class="mt-2 flex items-center gap-2 text-[11px] text-muted">
                                    <input type="checkbox" name="remove_favicon" value="1" class="size-4 rounded">
                                    حذف فاوآیکون سفارشی
                                </label>
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">ذخیره لوگو و فاوآیکون</button>
                </form>
            </section>

            {{-- محتوای صفحه اصلی --}}
            <section class="card space-y-5">
                <div>
                    <h2 class="text-base">محتوای صفحه اصلی</h2>
                    <p class="mt-1 text-xs text-muted">این متن‌ها در صفحهٔ اول سایت نشان داده می‌شوند؛ خالی بگذاری، متن پیش‌فرض برمی‌گردد.</p>
                </div>

                <form method="POST" action="{{ route('panel.settings.home') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @php
                        $homeLabels = [
                            'home_hero_line' => 'خط دوم تیتر بزرگ صفحه اول',
                            'home_cta_primary' => 'دکمهٔ اصلی هیرو',
                            'home_cta_secondary' => 'دکمهٔ دوم هیرو',
                            'home_why_title' => 'تیتر بخش «چرا با هم»',
                            'home_banner_title' => 'تیتر بنر پایانی',
                            'home_banner_text' => 'متن بنر پایانی',
                        ];
                    @endphp

                    @foreach ($homeLabels as $key => $label)
                        <div>
                            <label class="label" for="{{ $key }}">{{ $label }}</label>
                            <input id="{{ $key }}" name="{{ $key }}" type="text" maxlength="500"
                                   value="{{ old($key, $home[$key]) }}" class="input"
                                   placeholder="{{ \App\Models\Setting::HOME_DEFAULTS[$key] }}">
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">ذخیره محتوای صفحه اصلی</button>
                </form>
            </section>

            {{-- حالت تعمیر سایت --}}
            <section class="card self-start space-y-4 {{ $maintenanceMode ? 'ring-2 ring-gold' : '' }}">
                <div>
                    <h2 class="flex flex-wrap items-center gap-2 text-base">
                        🛠️ حالت تعمیر سایت

                        <span class="badge {{ $maintenanceMode ? 'bg-gold/20 text-gold-ink ring-gold/50' : 'bg-leaf/10 text-forest ring-leaf/30' }}">
                            {{ $maintenanceMode ? 'روشن — سایت در حال تعمیر است' : 'خاموش — سایت باز است' }}
                        </span>
                    </h2>

                    <p class="mt-2 max-w-2xl text-xs leading-6 text-muted">
                        وقتی روشن باشد، بازدیدکننده‌های سایت صفحه «در حال تعمیر» با پیام زیر را می‌بینند (وضعیت ۵۰۳).
                        <span class="font-semibold text-ink">پنل مدیریت و داشبورد اختصاصی شرکت‌کننده‌ها باز می‌ماند</span>
                        تا کار چالش‌ها نخوابد.
                    </p>
                </div>

                <form method="POST" action="{{ route('panel.settings.maintenance') }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="maintenance_mode" value="{{ $maintenanceMode ? '0' : '1' }}">

                    <div>
                        <label class="label" for="maintenance_message">پیام صفحه «در حال تعمیر»</label>
                        <textarea id="maintenance_message" name="maintenance_message" rows="3" class="input resize-y" placeholder="{{ \App\Models\Setting::DEFAULT_MAINTENANCE_MESSAGE }}">{{ old('maintenance_message', $maintenanceMessage) }}</textarea>
                        <p class="hint">خالی بگذاری، پیام پیش‌فرض نشان داده می‌شود.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" name="save_message" value="1" class="btn btn-white">ذخیرهٔ پیام</button>

                        <button type="submit" class="btn {{ $maintenanceMode ? 'btn-primary' : 'btn-gold' }}">
                            {{ $maintenanceMode ? 'خاموش کردن حالت تعمیر' : 'روشن کردن حالت تعمیر' }}
                        </button>
                    </div>
                </form>

                @if ($maintenanceMode)
                    <p class="rounded-2xl bg-gold/15 px-4 py-3 text-xs leading-6 text-ink ring-1 ring-gold/50">
                        همین حالا <a href="{{ route('home') }}" target="_blank" class="font-semibold text-gold-ink underline">صفحه اصلی سایت</a>
                        برای بازدیدکننده‌ها صفحه تعمیر را نشان می‌دهد.
                    </p>
                @endif
            </section>
        @endif

    </div>
@endsection
