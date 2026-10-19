@php($themeCss = \App\Models\Setting::themeCss())
@if ($themeCss)
    <style id="theme-vars">{!! $themeCss !!}</style>
@endif
