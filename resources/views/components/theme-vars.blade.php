@php
    $themeCss = \App\Models\Setting::themeCss();
@endphp
@if ($themeCss)
    <style id="theme-vars">{!! $themeCss !!}</style>
@endif
