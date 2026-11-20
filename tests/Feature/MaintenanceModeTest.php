<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * حالت تعمیر سایت از پنل:
 * - وقتی روشن است، صفحه‌های عمومی ۵۰۳ با پیام مدیر می‌شوند
 * - پنل مدیریت و داشبورد شرکت‌کننده‌ها باز می‌مانند
 * - سوییچ سریع فقط در اختیار مدیر اصلی است
 */
class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(): Challenge
    {
        return Challenge::create([
            'title' => 'چالش تعمیر',
            'slug' => 'maintenance',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ]);
    }

    public function test_the_site_is_open_by_default(): void
    {
        $this->assertFalse(Setting::maintenanceMode());
        $this->get(route('home'))->assertOk();
    }

    public function test_the_super_admin_can_toggle_maintenance_mode(): void
    {
        $this->loginAsAdmin();

        $this->patch(route('panel.settings.maintenance'), ['maintenance_mode' => '1'])->assertRedirect();
        $this->assertTrue(Setting::maintenanceMode());

        $this->patch(route('panel.settings.maintenance'), ['maintenance_mode' => '0'])->assertRedirect();
        $this->assertFalse(Setting::maintenanceMode());
    }

    public function test_a_regular_admin_can_not_toggle_maintenance_mode(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin());

        $this->patch(route('panel.settings.maintenance'), ['maintenance_mode' => '1'])->assertForbidden();
        $this->assertFalse(Setting::maintenanceMode());
    }

    public function test_public_pages_show_the_maintenance_page_while_it_is_on(): void
    {
        $challenge = $this->makeChallenge();
        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        Setting::put('maintenance_mode', '1');
        Setting::put('maintenance_message', 'داریم سایت را رنگ می‌زنیم!');

        // صفحه‌های عمومی: ۵۰۳ با پیام مدیر
        foreach ([route('home'), route('challenges.index'), route('challenges.show', $challenge)] as $url) {
            $this->get($url)
                ->assertStatus(503)
                ->assertSee('داریم سایت را رنگ می‌زنیم!')
                ->assertSee('در حال تعمیر');
        }

        // پنل، ورود با کد و داشبورد شرکت‌کننده باز می‌مانند
        $this->loginAsAdmin();
        $this->get(route('panel.dashboard'))->assertOk();

        $this->get(route('participant.lookup'))->assertOk();
        $this->get(route('participant.show', $participant))->assertOk();
    }

    public function test_the_default_message_is_used_when_no_custom_message_is_set(): void
    {
        Setting::put('maintenance_mode', '1');

        $this->get(route('home'))
            ->assertStatus(503)
            ->assertSee(Setting::DEFAULT_MAINTENANCE_MESSAGE);
    }

    public function test_the_site_opens_again_after_switching_maintenance_off(): void
    {
        Setting::put('maintenance_mode', '1');
        $this->get(route('home'))->assertStatus(503);

        Setting::put('maintenance_mode', '0');
        $this->get(route('home'))->assertOk();
    }

    public function test_the_settings_page_has_the_maintenance_switch_and_message_field(): void
    {
        $this->loginAsAdmin();

        $html = $this->get(route('panel.settings.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('حالت تعمیر سایت', $html);
        $this->assertStringContainsString('name="maintenance_mode"', $html);
        $this->assertStringContainsString('name="maintenance_message"', $html);
        $this->assertStringContainsString('روشن کردن حالت تعمیر', $html);
    }

    public function test_the_custom_message_can_be_saved_from_the_settings_form(): void
    {
        $this->loginAsAdmin();

        $this->put(route('panel.settings.update'), [
            'site_name' => 'پروانه شو',
            'site_tagline' => '',
            'site_about' => '',
            'maintenance_message' => 'فردا صبح برمی‌گردیم.',
        ])->assertRedirect();

        $this->assertSame('فردا صبح برمی‌گردیم.', Setting::get('maintenance_message'));
        $this->assertSame('فردا صبح برمی‌گردیم.', Setting::maintenanceMessage());
    }

    public function test_maintenance_message_saves_from_the_maintenance_box(): void
    {
        $this->loginAsAdmin();

        $this->patch(route('panel.settings.maintenance'), [
            'maintenance_message' => 'به‌زودی برمی‌گردیم',
            'save_message' => '1',
        ])->assertRedirect();

        $this->assertSame('به‌زودی برمی‌گردیم', Setting::get('maintenance_message'));
        // ذخیرهٔ پیام، حالت تعمیر را روشن/خاموش نمی‌کند
        $this->assertFalse(Setting::maintenanceMode());
    }
}
