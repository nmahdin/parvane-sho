<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مدیریت محتوای صفحه اصلی از پنل (فقط مدیر اصلی).
 */
class HomeContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_default_content(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('هر روز یک قدم کوچک، یک تغییر واقعی')
            ->assertSee('چرا با هم و در چالش؟');
    }

    public function test_super_admin_can_change_home_content(): void
    {
        $this->loginAsAdmin();

        $this->put(route('panel.settings.home'), [
            'home_hero_line' => 'یک عادت کوچک، یک زندگی بزرگ',
            'home_why_title' => 'چرا تنهایی سخت است؟',
        ])->assertRedirect();

        $this->get(route('home'))->assertOk()
            ->assertSee('یک عادت کوچک، یک زندگی بزرگ')
            ->assertSee('چرا تنهایی سخت است؟')
            ->assertDontSee('هر روز یک قدم کوچک، یک تغییر واقعی');
    }

    public function test_empty_value_restores_default(): void
    {
        $this->loginAsAdmin();

        $this->put(route('panel.settings.home'), ['home_hero_line' => 'متن دلخواه'])->assertRedirect();
        $this->get(route('home'))->assertOk()->assertSee('متن دلخواه');

        $this->put(route('panel.settings.home'), ['home_hero_line' => ''])->assertRedirect();
        $this->get(route('home'))->assertOk()->assertSee('هر روز یک قدم کوچک، یک تغییر واقعی');
    }

    public function test_regular_admin_cannot_change_home_content(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'helper-admin']));

        $this->put(route('panel.settings.home'), ['home_hero_line' => 'هک'])->assertForbidden();
    }
}
