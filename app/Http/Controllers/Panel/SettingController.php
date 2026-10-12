<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('panel.settings', [
            'site_name' => Setting::get('site_name', config('panel.site_name')),
            'site_tagline' => Setting::get('site_tagline', config('panel.site_tagline')),
            'site_about' => Setting::get('site_about', config('panel.site_about')),
            'useDefaultPassword' => Setting::panelPasswordHash() === null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:500'],
            'site_about' => ['nullable', 'string', 'max:2000'],
        ], [
            'site_name.required' => 'نام سایت را وارد کنید.',
        ]);

        Setting::put('site_name', $validated['site_name']);
        Setting::put('site_tagline', $validated['site_tagline'] ?? '');
        Setting::put('site_about', $validated['site_about'] ?? '');

        return back()->with('status', 'تنظیمات سایت ذخیره شد.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'max:190', 'confirmed'],
        ], [
            'current_password.required' => 'رمز عبور فعلی را وارد کنید.',
            'password.required' => 'رمز عبور جدید را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور یکسان نیست.',
        ]);

        $hash = Setting::panelPasswordHash();

        $currentMatches = $hash
            ? Hash::check($validated['current_password'], $hash)
            : hash_equals((string) config('panel.password'), $validated['current_password']);

        if (! $currentMatches) {
            throw ValidationException::withMessages([
                'current_password' => 'رمز عبور فعلی درست نیست.',
            ]);
        }

        Setting::setPanelPassword($validated['password']);

        return back()->with('status', 'رمز عبور پنل تغییر کرد. از این پس با رمز جدید وارد می‌شوید.');
    }
}
