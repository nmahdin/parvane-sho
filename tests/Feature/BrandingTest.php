<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * آپلود لوگو و فاوآیکون از بخش مدیریت کل:
 * - فقط مدیر اصلی
 * - فایل‌ها ذخیره و از /branding/{kind} سرو می‌شوند
 * - تا آپلود نشده، پروانهٔ پیش‌فرض نشان داده می‌شود
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_upload_logo_and_favicon(): void
    {
        Storage::fake('local');
        $this->loginAsAdmin();

        $this->post(route('panel.settings.branding'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
            'favicon' => UploadedFile::fake()->image('fav.png', 64, 64),
        ])->assertRedirect()->assertSessionHas('status');

        $logo = Setting::get('site_logo');
        $favicon = Setting::get('site_favicon');
        $this->assertNotNull($logo);
        $this->assertNotNull($favicon);
        Storage::disk('local')->assertExists('branding/'.$logo);
        Storage::disk('local')->assertExists('branding/'.$favicon);

        // فایل‌ها از مسیر عمومی سرو می‌شوند و در صفحه دیده می‌شوند
        $this->get(route('branding', 'logo'))->assertOk();
        $this->get(route('branding', 'favicon'))->assertOk();
        $this->get(route('home'))->assertOk()->assertSee('/branding/logo', false);
    }

    public function test_branding_falls_back_to_default_butterfly(): void
    {
        $this->get(route('branding', 'logo'))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertSee('favicon.svg', false);
    }

    public function test_non_image_logo_is_rejected(): void
    {
        $this->loginAsAdmin();

        $this->post(route('panel.settings.branding'), [
            'logo' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('logo');

        $this->assertNull(Setting::get('site_logo'));
    }

    public function test_logo_can_be_removed(): void
    {
        Storage::fake('local');
        $this->loginAsAdmin();

        $this->post(route('panel.settings.branding'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();
        $this->assertNotNull(Setting::get('site_logo'));

        $this->post(route('panel.settings.branding'), ['remove_logo' => '1'])->assertRedirect();
        $this->assertNull(Setting::get('site_logo'));
    }

    public function test_regular_admin_cannot_change_branding(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'helper-admin']));

        $this->post(route('panel.settings.branding'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertForbidden();
    }
}
