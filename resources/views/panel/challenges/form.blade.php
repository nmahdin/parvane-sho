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
                    <label for="description" class="label">توضیحات کامل</label>
                    <textarea id="description" name="description" rows="6" class="input resize-y" placeholder="درباره این چالش، قوانین و انتظارات بنویس...">{{ old('description', $challenge->description) }}</textarea>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="starts_on" class="label">تاریخ شروع</label>
                        <input id="starts_on" name="starts_on" type="text" value="{{ old('starts_on', \App\Support\Jalali::inputValue($challenge->starts_on)) }}" class="input" placeholder="۱۴۰۵/۰۷/۲۰" inputmode="numeric">
                        <p class="hint">تاریخ شمسی را مثل ۱۴۰۵/۰۷/۲۰ بنویسید. خالی گذاشتن هم اشکالی ندارد.</p>
                    </div>

                    <div>
                        <label for="ends_on" class="label">تاریخ پایان</label>
                        <input id="ends_on" name="ends_on" type="text" value="{{ old('ends_on', \App\Support\Jalali::inputValue($challenge->ends_on)) }}" class="input" placeholder="۱۴۰۵/۰۸/۱۰" inputmode="numeric">
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
                                <button type="button" data-emoji="#emoji" data-emoji-value="{{ $emoji }}" class="grid size-10 place-items-center rounded-xl bg-slate-50 text-lg ring-1 ring-slate-900/5 transition hover:bg-brand-50">
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

                    <label class="flex items-start gap-3 rounded-2xl bg-slate-50 p-4">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $challenge->is_published)) class="mt-1 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block text-sm text-slate-800">در سایت نمایش داده شود</span>
                            <span class="mt-0.5 block text-xs text-slate-500">اگر تیک را برنداری، چالش فقط در پنل دیده می‌شود.</span>
                        </span>
                    </label>
                </section>

                <section class="card space-y-3">
                    <button type="submit" class="btn btn-primary w-full py-3">{{ $isEdit ? 'ذخیره تغییرات' : 'ساخت چالش' }}</button>
                    <a href="{{ $isEdit ? route('panel.challenges.show', $challenge) : route('panel.challenges.index') }}" class="btn btn-white w-full">انصراف</a>
                </section>
            </div>
        </div>
    </form>
@endsection
