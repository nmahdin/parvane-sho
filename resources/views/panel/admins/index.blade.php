@extends('layouts.panel')

@section('title', 'مدیران')
@section('page-title', 'مدیران پنل')
@section('page-subtitle', 'ساخت مدیر جدید، تغییر رمز و فعال/غیرفعال‌کردن حساب‌ها')

@section('content')
    {{-- پیام‌ها یک‌بار در چیدمان پنل نمایش داده می‌شوند --}}
    <section class="card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base">مدیر جدید</h2>
                <p class="mt-1 text-xs leading-6 text-muted">
                    هر مدیر با نام کاربری و رمز خودش وارد می‌شود و فقط چالش‌ها و شرکت‌کننده‌های خودش را می‌بیند.
                    مدیر اصلی همه چالش‌ها را می‌بیند.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('panel.admins.store') }}" class="mt-5 grid gap-4 lg:grid-cols-2">
            @csrf

            <div>
                <label class="label" for="new-name">نام مدیر</label>
                <input id="new-name" name="name" type="text" required value="{{ old('name') }}" class="input" placeholder="مثلاً مریم احمدی">
                @error('name')
                    <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="new-username">نام کاربری</label>
                <input id="new-username" name="username" type="text" required dir="ltr" value="{{ old('username') }}" class="input text-center" placeholder="maryam">
                @error('username')
                    <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="new-password">رمز عبور</label>
                <input id="new-password" name="password" type="text" required dir="ltr" class="input text-center" autocomplete="new-password">
                <p class="hint">حداقل ۶ کاراکتر؛ همین را به مدیر بده تا بعداً خودش عوضش کند.</p>
                @error('password')
                    <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label" for="new-password-confirmation">تکرار رمز عبور</label>
                <input id="new-password-confirmation" name="password_confirmation" type="text" required dir="ltr" class="input text-center" autocomplete="new-password">
            </div>

            <label class="flex items-center gap-2 text-xs text-ink/80 lg:col-span-2">
                <input type="checkbox" name="is_super" value="1" @checked(old('is_super')) class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                این نفر «مدیر اصلی» باشد (همه چالش‌ها، همه مدیران و تنظیمات سایت را می‌بیند)
            </label>

            <div class="lg:col-span-2">
                <button type="submit" class="btn btn-primary">ساخت مدیر</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base">فهرست مدیران</h2>
            <span class="badge bg-cream text-muted ring-line">{{ fa_num($admins->count()) }} مدیر</span>
        </div>

        <div class="mt-5 space-y-4">
            @foreach ($admins as $adminItem)
                <div class="rounded-2xl bg-cream p-4 surface">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-paper text-sm font-bold text-ink/80 ring-1 ring-line">
                                {{ mb_substr($adminItem->name, 0, 1) }}
                            </span>

                            <div>
                                <p class="flex items-center gap-2 text-sm font-semibold text-ink">
                                    {{ $adminItem->name }}
                                    @if ($adminItem->getKey() === $current->getKey())
                                        <span class="badge bg-jade/10 text-jade ring-jade/30">خودت</span>
                                    @endif
                                </p>

                                <p class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-muted">
                                    <span dir="ltr">{{ $adminItem->username }}</span>
                                    <span class="badge {{ $adminItem->roleClasses() }}">{{ $adminItem->roleLabel() }}</span>
                                    <span class="badge {{ $adminItem->isActive() ? 'bg-leaf/10 text-forest ring-leaf/30' : 'bg-danger-soft text-danger ring-danger/30' }}">
                                        {{ $adminItem->isActive() ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                    <span>{{ fa_num($adminItem->challenges_count) }} چالش</span>
                                    @if ($adminItem->last_login_at)
                                        <span>آخرین ورود: {{ fa_datetime($adminItem->last_login_at) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @unless ($adminItem->getKey() === $current->getKey())
                                <form method="POST" action="{{ route('panel.admins.toggle', $adminItem) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-white btn-sm">
                                        {{ $adminItem->isActive() ? 'غیرفعال کن' : 'فعال کن' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('panel.admins.destroy', $adminItem) }}"
                                      onsubmit="return confirm('مدیر «{{ $adminItem->name }}» حذف شود؟ چالش‌ها و شرکت‌کننده‌هایش به حساب شما منتقل می‌شوند.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-white btn-sm text-danger">حذف</button>
                                </form>
                            @endunless
                        </div>
                    </div>

                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs font-medium text-brand-700">ویرایش نام، نام کاربری و رمز</summary>

                        <form method="POST" action="{{ route('panel.admins.update', $adminItem) }}" class="mt-4 grid gap-4 lg:grid-cols-2">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="label" for="name-{{ $adminItem->id }}">نام مدیر</label>
                                <input id="name-{{ $adminItem->id }}" name="name" type="text" required value="{{ $adminItem->name }}" class="input">
                            </div>

                            <div>
                                <label class="label" for="username-{{ $adminItem->id }}">نام کاربری</label>
                                <input id="username-{{ $adminItem->id }}" name="username" type="text" required dir="ltr" value="{{ $adminItem->username }}" class="input text-center">
                            </div>

                            <div>
                                <label class="label" for="password-{{ $adminItem->id }}">رمز عبور جدید (اختیاری)</label>
                                <input id="password-{{ $adminItem->id }}" name="password" type="text" dir="ltr" class="input text-center" autocomplete="new-password" placeholder="خالی بگذار تا عوض نشود">
                            </div>

                            <div>
                                <label class="label" for="password-confirmation-{{ $adminItem->id }}">تکرار رمز جدید</label>
                                <input id="password-confirmation-{{ $adminItem->id }}" name="password_confirmation" type="text" dir="ltr" class="input text-center" autocomplete="new-password">
                            </div>

                            @unless ($adminItem->getKey() === $current->getKey())
                                <label class="flex items-center gap-2 text-xs text-ink/80 lg:col-span-2">
                                    <input type="checkbox" name="is_super" value="1" @checked($adminItem->isSuper()) class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                                    مدیر اصلی باشد
                                </label>
                            @endunless

                            <div class="lg:col-span-2">
                                <button type="submit" class="btn btn-primary btn-sm">ذخیره تغییرات</button>
                            </div>
                        </form>
                    </details>
                </div>
            @endforeach
        </div>
    </section>
@endsection
