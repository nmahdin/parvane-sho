@extends('layouts.panel')

@php
    $isEdit = $challenge->exists;
    $action = $isEdit ? route('panel.challenges.update', $challenge) : route('panel.challenges.store');
@endphp

@section('title', $isEdit ? 'ویرایش چالش' : 'چالش جدید')
@section('page-title', $isEdit ? 'ویرایش «'.$challenge->title.'»' : 'ساخت چالش جدید')
@section('page-subtitle', 'مشخصات چالش را وارد کنید؛ کارهای هر روز را در صفحه بعدی می‌سازید')

@section('content')
    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            <section class="card space-y-5">
                <h2 class="text-base">مشخصات اصلی</h2>

                <div>
                    <label for="title" class="label">عنوان چالش *</label>
                    <input id="title" name="title" type="text" required value="{{ old('title', $challenge->title) }}" class="input" placeholder="مثلاً چالش ۲۱ روزه شکرگزاری">
                </div>

                <div>
                    <label for="tagline" class="label">توضیح کوتاه</label>
                    <input id="tagline" name="tagline" type="text" value="{{ old('tagline', $challenge->tagline) }}" class="input" placeholder="یک جمله که در فهرست چالش‌ها زیر عنوان دیده می‌شود">
                </div>

                <div>
                    <label for="description" class="label">توضیح کوتاه چالش</label>
                    <textarea id="description" name="description" rows="3" class="input resize-y" placeholder="یک پاراگراف کوتاه که بالای صفحه چالش دیده می‌شود...">{{ old('description', $challenge->description) }}</textarea>
                </div>

                <div>
                    <label for="long_description" class="label">توضیحات بلند</label>
                    <textarea id="long_description" name="long_description" rows="10" class="input resize-y" placeholder="قوانین، توضیح روزها، انتظارات و هر نکته‌ای که لازم است...">{{ old('long_description', $challenge->long_description) }}</textarea>

                    <div class="mt-2 rounded-2xl bg-cream px-4 py-3 text-[11px] leading-6 text-muted">
                        <p class="font-medium text-ink/80">قالب‌بندی ساده پشتیبانی می‌شود:</p>
                        <ul class="mt-1.5 space-y-0.5">
                            <li>– عنوان با <code class="rounded bg-paper px-1" dir="ltr">## عنوان</code></li>
                            <li>– فهرست با شروع خط با <code class="rounded bg-paper px-1" dir="ltr">-</code></li>
                            <li>– متن پررنگ با <code class="rounded bg-paper px-1" dir="ltr">**متن**</code></li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label for="contact_note" class="label">راه ارتباطی با مسئول چالش (اختیاری)</label>
                    <input id="contact_note" name="contact_note" type="text" value="{{ old('contact_note', $challenge->contact_note) }}" class="input" placeholder="مثلاً: برای هماهنگی به آیدی @parvaneh_admin پیام بده">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="starts_on" class="label">تاریخ شروع</label>
                        <div class="relative">
                            <input id="starts_on" name="starts_on" type="text" data-jalali-picker value="{{ old('starts_on', \App\Support\Jalali::inputValue($challenge->starts_on)) }}" class="input pe-11" placeholder="۱۴۰۵/۰۷/۲۰" inputmode="numeric">
                            <span class="pointer-events-none absolute inset-y-0 end-0 grid w-11 place-items-center text-muted/80">🗓️</span>
                        </div>
                        <p class="hint">با تقویم انتخاب کن یا دستی مثل ۱۴۰۵/۰۷/۲۰ بنویس. برای آزادسازی روزبه‌روز، همین تاریخ روز اول چالش است.</p>
                    </div>

                    <div>
                        <label for="ends_on" class="label">تاریخ پایان</label>
                        <div class="relative">
                            <input id="ends_on" name="ends_on" type="text" data-jalali-picker value="{{ old('ends_on', \App\Support\Jalali::inputValue($challenge->ends_on)) }}" class="input pe-11" placeholder="۱۴۰۵/۰۸/۱۰" inputmode="numeric">
                            <span class="pointer-events-none absolute inset-y-0 end-0 grid w-11 place-items-center text-muted/80">🗓️</span>
                        </div>
                        <p class="hint">نمی‌تواند قبل از تاریخ شروع باشد.</p>
                    </div>
                </div>
            </section>

            <div class="space-y-6">
                <section class="card space-y-5">
                    <h2 class="text-base">ظاهر و انتشار</h2>

                    <div>
                        <label for="emoji" class="label">نماد چالش</label>
                        <input id="emoji" name="emoji" type="text" value="{{ old('emoji', $challenge->emoji) }}" class="input text-center text-xl" maxlength="8">

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach (['🦋', '🌱', '🌙', '💧', '🏃', '📖', '🧘', '🍎', '✍️', '💪', '☀️', '🎯'] as $emoji)
                                <button type="button" data-emoji="#emoji" data-emoji-value="{{ $emoji }}" class="grid size-10 place-items-center rounded-xl bg-cream text-lg ring-1 ring-line transition hover:bg-brand-50">
                                    {{ $emoji }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="accent" class="label">رنگ چالش</label>
                        <select id="accent" name="accent" class="input">
                            @foreach (\App\Models\Challenge::ACCENTS as $value => $label)
                                <option value="{{ $value }}" @selected(old('accent', $challenge->accent) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($adminOptions->isNotEmpty())
                        <div>
                            <label for="admin_id" class="label">مدیر این چالش</label>
                            <select id="admin_id" name="admin_id" class="input">
                                @foreach ($adminOptions as $adminOption)
                                    <option value="{{ $adminOption->id }}" @selected((int) old('admin_id', $challenge->admin_id) === (int) $adminOption->id)>
                                        {{ $adminOption->name }} ({{ $adminOption->username }}) — {{ $adminOption->roleLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="hint">مدیر اصلی می‌تواند چالش را به یک مدیر چالش بسپارد؛ آن مدیر فقط چالش‌های خودش را می‌بیند.</p>
                        </div>
                    @endif

                    <div>
                        <label for="slug" class="label">آدرس انگلیسی (اختیاری)</label>
                        <input id="slug" name="slug" type="text" dir="ltr" value="{{ old('slug', $isEdit ? $challenge->slug : '') }}" class="input" placeholder="gratitude-21">
                        <p class="hint">فقط حروف کوچک لاتین، عدد و خط تیره. اگر خالی بگذاری، خودکار ساخته می‌شود.</p>
                    </div>

                    <div>
                        <label for="sort_order" class="label">ترتیب نمایش</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $challenge->sort_order) }}" class="input">
                        <p class="hint">عدد کوچک‌تر، جلوتر نشان داده می‌شود.</p>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl bg-cream p-4">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $challenge->is_published)) class="mt-1 size-4 rounded border-line text-jade focus:ring-jade">
                        <span>
                            <span class="block text-sm text-ink">در سایت نمایش داده شود</span>
                            <span class="mt-0.5 block text-xs text-muted">اگر تیک را برنداری، چالش فقط در پنل دیده می‌شود.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl bg-gold/15 p-4 ring-1 ring-gold/50">
                        <input type="checkbox" name="daily_release" value="1" @checked(old('daily_release', $challenge->daily_release ?? true)) class="mt-1 size-4 rounded border-gold/60 text-jade focus:ring-jade">
                        <span>
                            <span class="block text-sm font-medium text-ink">🔒 آزادسازی روزبه‌روز (قفل بودن روزهای بعدی)</span>
                            <span class="mt-0.5 block text-xs leading-6 text-ink/80">
                                اگر فعال باشد، هر روز فقط کارهای همان روز و روزهای قبل باز است و شرکت‌کننده نمی‌تواند روزهای بعدی را ببیند یا تیک بزند.
                                روز اول بر اساس <strong>تاریخ شروع</strong> (و اگر خالی باشد، روز ساخت چالش) حساب می‌شود.
                            </span>
                        </span>
                    </label>
                </section>

                <section class="card space-y-5">
                    <div>
                        <h2 class="text-base">تعهدنامه ورود شرکت‌کننده‌ها</h2>
                        <p class="mt-1 text-xs leading-6 text-muted">
                            اگر فعال باشد، هر شرکت‌کننده اولین بار که با لینک اختصاصی‌اش وارد می‌شود، این متن را می‌بیند و
                            تا وقتی آن را نپذیرد کارهای چالش برایش باز نمی‌شود. اگر متن را خالی بگذاری، متن پیش‌فرض استفاده می‌شود.
                        </p>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl bg-cream p-4">
                        <input type="checkbox" name="pledge_enabled" value="1" @checked(old('pledge_enabled', $challenge->pledge_enabled ?? true)) class="mt-1 size-4 rounded border-line text-jade focus:ring-jade">
                        <span>
                            <span class="block text-sm text-ink">تعهدنامه در اولین ورود نمایش داده شود</span>
                            <span class="mt-0.5 block text-xs leading-6 text-muted">
                                متن تعهد را می‌توانی همین‌جا برای هر چالش جداگانه بنویسی و بعداً از همین صفحه ویرایش کنی.
                            </span>
                        </span>
                    </label>

                    <div>
                        <label for="pledge_title" class="label">عنوان تعهدنامه</label>
                        <input id="pledge_title" name="pledge_title" type="text" maxlength="190"
                               value="{{ old('pledge_title', $challenge->pledge_title) }}"
                               class="input" placeholder="{{ \App\Models\Challenge::DEFAULT_PLEDGE_TITLE }}">
                    </div>

                    <div>
                        <label for="pledge_text" class="label">متن تعهدنامه</label>
                        <textarea id="pledge_text" name="pledge_text" rows="8" class="input resize-y"
                                  placeholder="{{ \App\Models\Challenge::DEFAULT_PLEDGE_TEXT }}">{{ old('pledge_text', $challenge->pledge_text) }}</textarea>
                        <p class="hint">هر خط، یک خط از تعهدنامه می‌شود؛ خط‌هایی که با «-» شروع شوند فهرست می‌شوند.</p>
                    </div>
                </section>

                <section class="card space-y-3">
                    <button type="submit" class="btn btn-primary w-full py-3">{{ $isEdit ? 'ذخیره تغییرات' : 'ساخت چالش' }}</button>
                    <a href="{{ $isEdit ? route('panel.challenges.show', $challenge) : route('panel.challenges.index') }}" class="btn btn-white w-full">انصراف</a>
                </section>
            </div>
        </div>
    </form>
@endsection
