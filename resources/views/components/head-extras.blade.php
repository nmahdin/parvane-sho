@php($customFavicon = \App\Models\Setting::get('site_favicon'))
@if ($customFavicon)
    <link rel="icon" href="{{ route('branding', 'favicon') }}">
@else
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48">
@endif
<script>document.documentElement.classList.add('has-js');</script>
