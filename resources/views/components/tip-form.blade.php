@props(['challenge', 'day' => null, 'dayChoices' => null, 'modal' => false])

@php
    $fixedDay = $day !== null && (int) $day > 0 ? (int) $day : null;
    $suffix = $fixedDay ? 'day-'.$fixedDay : 'general';
    $emojiId = 'tip-emoji-new-'.$suffix;
    $titleId = 'tip-title-new-'.$suffix;
    $bodyId = 'tip-body-new-'.$suffix;
    $dayId = 'tip-day-new-'.$suffix;
    $dayChoices ??= collect();
@endphp

<form method="POST" action="{{ route('panel.tips.store', $challenge) }}" data-ajax-save {{ $attributes->merge(['class' => 'mt-4 grid gap-4 rounded-2xl bg-cream p-4 text-right sm:grid-cols-[6rem_1fr]']) }}>
    @csrf

    @if ($fixedDay)
        <input type="hidden" name="day_number" value="{{ $fixedDay }}">
    @elseif ($modal)
        {{-- داخل مودال، روز از دکمهٔ همان روز می‌آید؛ فیلد انتخاب روز ندارد --}}
        <input type="hidden" name="day_number" value="" data-modal-day-input>
    @endif

    <div>
        <label class="label" for="{{ $emojiId }}">نماد</label>
        <input id="{{ $emojiId }}" name="emoji" type="text" value="💡" maxlength="8" class="input text-center text-lg">

        <div class="mt-2 flex flex-wrap gap-1">
            @foreach (\App\Models\ChallengeTip::EMOJIS as $emoji)
                <button type="button" data-emoji="#{{ $emojiId }}" data-emoji-value="{{ $emoji }}" class="grid size-7 place-items-center rounded-lg bg-paper text-sm ring-1 ring-line hover:bg-gold/15">{{ $emoji }}</button>
            @endforeach
        </div>
    </div>

    <div class="space-y-3">
        <div>
            <label class="label" for="{{ $titleId }}">عنوان نکته</label>
            <input id="{{ $titleId }}" name="title" type="text" required class="input" placeholder="مثلاً آب کافی بنوش">
        </div>

        <div>
            <label class="label" for="{{ $bodyId }}">متن نکته (اختیاری)</label>
            <textarea id="{{ $bodyId }}" name="body" rows="2" class="input resize-y" placeholder="توضیح کوتاه این نکته..."></textarea>
        </div>

        @if (! $modal && $dayChoices->isNotEmpty())
            <div>
                <label class="label" for="{{ $dayId }}">این نکته مربوط به کدام روز است؟</label>
                <select id="{{ $dayId }}" name="day_number" class="input">
                    <option value="">بدون روز مشخص (نکته کلی چالش)</option>
                    @foreach ($dayChoices as $choice)
                        <option value="{{ $choice['day'] }}">روز {{ fa_num($choice['day']) }}@if (! empty($choice['label'])) · {{ $choice['label'] }} @endif</option>
                    @endforeach
                </select>
                <p class="hint">اگر یک روز را انتخاب کنی، نکته فقط در کارت همان روز دیده می‌شود.</p>
            </div>
        @endif

        @if (! $modal)
            <button type="submit" class="btn btn-primary btn-sm">{{ $fixedDay ? 'افزودن نکته به روز '.fa_num($fixedDay) : 'افزودن نکته' }}</button>
        @endif
    </div>

    @if ($modal)
        {{-- پاورقی مودال: انصراف راست، ثبت چپ، هم‌اندازه --}}
        <div class="flex items-center gap-2 sm:col-span-2">
            <button type="button" class="btn btn-ghost flex-1 justify-center" data-close-modal>انصراف</button>
            <button type="submit" class="btn btn-primary flex-1 justify-center">افزودن نکته</button>
        </div>
    @endif
</form>
