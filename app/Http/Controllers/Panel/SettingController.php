<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function edit(): View
    {
        $admin = $this->currentAdmin();

        return view('panel.settings', [
            'site_name' => Setting::get('site_name', config('panel.site_name')),
            'site_tagline' => Setting::get('site_tagline', config('panel.site_tagline')),
            'site_about' => Setting::get('site_about', config('panel.site_about')),
            'branding' => [
                'logo' => Setting::get('site_logo'),
                'favicon' => Setting::get('site_favicon'),
            ],
            'home' => Setting::home(),
            'themeColors' => Setting::themeColors(),
            'themeDefaults' => Setting::THEME_DEFAULTS,
            'maintenanceMode' => Setting::maintenanceMode(),
            'maintenanceMessage' => Setting::get('maintenance_message'),
            'admin' => $admin,
            'isSuper' => $admin->isSuper(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // تنظیمات کلی سایت فقط در اختیار مدیر اصلی است
        abort_unless($this->currentAdmin()->isSuper(), 403);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:500'],
            'site_about' => ['nullable', 'string', 'max:2000'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ], [
            'site_name.required' => 'نام سایت را وارد کنید.',
        ]);

        Setting::put('site_name', $validated['site_name']);
        Setting::put('site_tagline', $validated['site_tagline'] ?? '');
        Setting::put('site_about', $validated['site_about'] ?? '');
        Setting::put('maintenance_message', trim((string) ($validated['maintenance_message'] ?? '')));

        return back()->with('status', 'تنظیمات سایت ذخیره شد.');
    }

    /**
     * آپلود لوگو و فاوآیکون سایت (فقط مدیر اصلی).
     */
    public function updateBranding(Request $request): RedirectResponse
    {
        abort_unless($this->currentAdmin()->isSuper(), 403);

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:svg,png,ico,x-icon,webp,jpg,jpeg', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ], [
            'logo.image' => 'لوگو باید یک فایل تصویری باشد.',
            'logo.max' => 'حجم لوگو حداکثر ۲ مگابایت است.',
            'favicon.mimes' => 'فاوآیکون باید svg، png، ico، webp یا jpg باشد.',
            'favicon.max' => 'حجم فاوآیکون حداکثر ۵۱۲ کیلوبایت است.',
        ]);

        foreach (['logo', 'favicon'] as $kind) {
            if ($request->boolean('remove_'.$kind)) {
                $old = Setting::get('site_'.$kind);

                if (is_string($old) && $old !== '') {
                    Storage::disk('local')->delete('branding/'.$old);
                }

                Setting::put('site_'.$kind, null);

                continue;
            }

            $file = $request->file($kind);

            if (! $file) {
                continue;
            }

            $old = Setting::get('site_'.$kind);

            if (is_string($old) && $old !== '') {
                Storage::disk('local')->delete('branding/'.$old);
            }

            $name = $kind.'-'.now()->timestamp.'.'.$file->getClientOriginalExtension();
            Storage::disk('local')->putFileAs('branding', $file, $name);
            Setting::put('site_'.$kind, $name);
        }

        unset($validated);

        return back()->with('status', 'لوگو و فاوآیکون به‌روزرسانی شد.');
    }

    /**
     * مدیریت محتوای صفحه اصلی (فقط مدیر اصلی).
     */
    public function updateHome(Request $request): RedirectResponse
    {
        abort_unless($this->currentAdmin()->isSuper(), 403);

        $rules = [];

        foreach (array_keys(Setting::HOME_DEFAULTS) as $key) {
            $rules[$key] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        foreach (array_keys(Setting::HOME_DEFAULTS) as $key) {
            $value = trim((string) ($validated[$key] ?? ''));

            if ($value === '' || $value === Setting::HOME_DEFAULTS[$key]) {
                Setting::put($key, null);

                continue;
            }

            Setting::put($key, $value);
        }

        return back()->with('status', 'محتوای صفحه اصلی ذخیره شد.');
    }

    /**
     * تغییر رنگ‌های سایت (فقط مدیر اصلی) — همان توکن‌های CSS ذخیره می‌شوند
     * و در <head> همه صفحه‌ها به‌صورت متغیر :root تزریق می‌شوند.
     */
    public function updateTheme(Request $request): RedirectResponse
    {
        abort_unless($this->currentAdmin()->isSuper(), 403);

        if ($request->boolean('reset')) {
            Setting::put('theme_colors', null);

            return back()->with('status', 'رنگ‌های سایت به حالت پیش‌فرض برگشت.');
        }

        $rules = [];

        foreach (array_keys(Setting::THEME_DEFAULTS) as $key) {
            $rules['colors.'.$key] = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        $validated = $request->validate($rules, [
            'colors.*.regex' => 'کد رنگ نامعتبر است؛ نمونهٔ معتبر: #659287',
        ]);

        $colors = [];

        foreach (($validated['colors'] ?? []) as $key => $value) {
            if ($value && strtoupper($value) !== Setting::THEME_DEFAULTS[$key]) {
                $colors[$key] = strtoupper($value);
            }
        }

        Setting::put('theme_colors', $colors ? json_encode($colors) : null);

        return back()->with('status', 'رنگ‌های سایت ذخیره شد و همان لحظه در همه صفحه‌ها اعمال شد.');
    }

    /**
     * روشن/خاموش کردن سریع حالت تعمیر سایت (فقط مدیر اصلی).
     */
    public function toggleMaintenance(Request $request): RedirectResponse
    {
        abort_unless($this->currentAdmin()->isSuper(), 403);

        $validated = $request->validate([
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        // پیام صفحهٔ تعمیر حالا در همین باکس ویرایش و ذخیره می‌شود
        if ($request->has('maintenance_message')) {
            Setting::put('maintenance_message', trim((string) ($validated['maintenance_message'] ?? '')));
        }

        if ($request->filled('save_message')) {
            return back()->with('status', 'پیام صفحه «در حال تعمیر» ذخیره شد.');
        }

        $enabled = $request->input('maintenance_mode') === '1';

        Setting::put('maintenance_mode', $enabled ? '1' : '0');

        return back()->with(
            'status',
            $enabled
                ? 'حالت تعمیر روشن شد؛ صفحه‌های عمومی سایت پیام تعمیر را نشان می‌دهند. پنل و داشبورد شرکت‌کننده‌ها باز است.'
                : 'حالت تعمیر خاموش شد؛ سایت دوباره برای همه باز است.'
        );
    }

    /**
     * تغییر رمز خود مدیر (هر مدیری رمز خودش را عوض می‌کند).
     */
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

        $admin = $this->currentAdmin();

        if (! Hash::check($validated['current_password'], $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'رمز عبور فعلی درست نیست.',
            ]);
        }

        $admin->update(['password' => $validated['password']]);

        return back()->with('status', 'رمز عبور شما تغییر کرد. از این پس با رمز جدید وارد می‌شوید.');
    }

    private function currentAdmin(): Admin
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return $admin;
    }
}
