@props(['showErrors' => true])

@php
    /* پیام‌های فلش دیگر بنر بالای صفحه نیستند؛ به‌صورت دادهٔ JSON
       در صفحه می‌نشینند و جاوااسکریپت همان‌ها را به توست تبدیل می‌کند. */
    $flashMessages = [];

    if (session('status')) {
        $flashMessages[] = ['type' => 'status', 'text' => session('status')];
    }

    if (session('error')) {
        $flashMessages[] = ['type' => 'error', 'text' => session('error')];
    }

    if ($showErrors && $errors->any()) {
        $flashMessages[] = ['type' => 'error', 'text' => $errors->first()];
    }
@endphp

@if ($flashMessages)
    <script type="application/json" id="flash-data">{!! json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
