<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * رنگ‌های سایت از پنل قابل تغییر و ذخیره هستند:
 * - فقط مدیر اصلی
 * - کدهای هگز معتبر ذخیره می‌شوند و در <head> به‌صورت :root تزریق می‌شوند
 * - بازنشانی، رنگ‌ها را به پیش‌فرض برمی‌گرداند
 */
class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_the_color_settings_form(): void
    {
        $this->loginAsAdmin();

        $html = $this->get(route('panel.settings.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('رنگ‌های سایت', $html);
        $this->assertStringContainsString('name="colors[jade]"', $html);
        $this->assertStringContainsString('name="colors[shell]"', $html);
    }

    public function test_theme_colors_can_be_saved_and_applied_site_wide(): void
    {
        $this->loginAsAdmin();

        $this->put(route('panel.settings.theme'), [
            'colors' => ['jade' => '#123456', 'leaf' => '#B8C9A3'],
        ])->assertRedirect();

        $stored = json_decode((string) Setting::get('theme_colors'), true);
        $this->assertSame('#123456', $stored['jade'] ?? null);
        $this->assertArrayNotHasKey('leaf', $stored ?? [], 'رنگ بدون تغییر ذخیره نمی‌شود.');

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('--color-jade: #123456', $home);
        $this->assertStringContainsString('--color-brand-500: #123456', $home);

        $panel = $this->get(route('panel.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('id="theme-vars"', $panel);
    }

    public function test_invalid_color_codes_are_rejected(): void
    {
        $this->loginAsAdmin();

        $this->put(route('panel.settings.theme'), [
            'colors' => ['jade' => 'red;'],
        ])->assertSessionHasErrors('colors.jade');

        $this->assertNull(Setting::get('theme_colors'));
    }

    public function test_theme_colors_can_be_reset_to_defaults(): void
    {
        $this->loginAsAdmin();

        $this->put(route('panel.settings.theme'), ['colors' => ['jade' => '#123456']])->assertRedirect();
        $this->assertNotNull(Setting::get('theme_colors'));

        $this->put(route('panel.settings.theme'), ['reset' => '1'])->assertRedirect();
        $this->assertNull(Setting::get('theme_colors'));

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('theme-vars', $home);
    }

    public function test_regular_admin_cannot_change_theme_colors(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'helper-admin']));

        $this->put(route('panel.settings.theme'), ['colors' => ['jade' => '#123456']])->assertForbidden();
    }
}
