@props(['challenge', 'tip', 'withDayPicker' => false, 'dayChoices' => null])

@php
    $emojiId = 'tip-emoji-'.$tip->getKey();
    $dayChoices ??= collect();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3xl bg-gradient-to-tl from-gold/40 to-paper p-4 surface']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-paper text-lg shadow-sm ring-1 ring-gold/50">{{ $tip->emoji }}</span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-forest">{{ $tip->title }}</p>
                @if ($tip->body)
                    <p class="mt-1 text-xs leading-6 text-ink/80">{{ $tip->body }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1">
            <form method="POST" action="{{ route('panel.tips.move', [$challenge, $tip, 'up']) }}" data-ajax-save>
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-ghost btn-sm" aria-label="بالا">↑</button>
            </form>
            <form method="POST" action="{{ route('panel.tips.move', [$challenge, $tip, 'down']) }}" data-ajax-save>
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-ghost btn-sm" aria-label="پایین">↓</button>
            </form>

            <button type="button" class="btn btn-ghost btn-sm" data-open-modal="edit-tips-{{ $tip->getKey() }}" data-modal-title="ویرایش نکته">ویرایش</button>

            <dialog id="edit-tips-{{ $tip->getKey() }}" class="panel-modal">
                <div class="panel-modal-box">
                    <div class="panel-modal-head">
                        <h3 class="text-sm font-semibold text-ink"><span data-modal-heading>ویرایش نکته</span></h3>
                        <button type="button" class="icon-btn" data-close-modal aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="size-4"><path d="M6 6l12 12"/><path d="M18 6L6 18"/></svg></button>
                    </div>

                    <form method="POST" action="{{ route('panel.tips.update', [$challenge, $tip]) }}" data-ajax-save data-modal-form class="grid gap-3 text-right sm:grid-cols-[5rem_1fr]">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="label" for="{{ $emojiId }}">نماد</label>
                        <input id="{{ $emojiId }}" type="text" name="emoji" value="{{ $tip->emoji }}" maxlength="8" class="input text-center">

                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach (\App\Models\ChallengeTip::EMOJIS as $emoji)
                                <button type="button" data-emoji="#{{ $emojiId }}" data-emoji-value="{{ $emoji }}" class="grid size-7 place-items-center rounded-lg bg-cream text-sm ring-1 ring-line hover:bg-gold/15">{{ $emoji }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="label" for="tip-title-{{ $tip->getKey() }}">عنوان</label>
                        <input id="tip-title-{{ $tip->getKey() }}" type="text" name="title" value="{{ $tip->title }}" required class="input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="tip-body-{{ $tip->getKey() }}">متن</label>
                        <textarea id="tip-body-{{ $tip->getKey() }}" name="body" rows="2" class="input resize-y">{{ $tip->body }}</textarea>
                    </div>

                    @if ($withDayPicker)
                        <div class="sm:col-span-2">
                            <label class="label" for="tip-day-{{ $tip->getKey() }}">این نکته مربوط به کدام روز است؟</label>
                            <select id="tip-day-{{ $tip->getKey() }}" name="day_number" class="input">
                                <option value="" @selected($tip->day_number === null)>بدون روز مشخص (نکته کلی چالش)</option>
                                @foreach ($dayChoices as $choice)
                                    <option value="{{ $choice['day'] }}" @selected((int) $tip->day_number === (int) $choice['day'])>
                                        روز {{ fa_num($choice['day']) }}@if ($choice['label']) · {{ $choice['label'] }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="hint">با انتخاب یک روز، نکته فقط در کارت همان روز نشان داده می‌شود.</p>
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                    </div>
                </form>
                </div>
            </dialog>

            <form method="POST" action="{{ route('panel.tips.destroy', [$challenge, $tip]) }}" data-ajax-save data-confirm="این نکته حذف شود؟">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
            </form>
        </div>
    </div>
</div>
