<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پوستهٔ پنل و سایت: منوی عملیات چالش‌ها، اسکریپت has-js (انیمیشن بدون
 * پرش)، چیپ بارگذاری صفحه عمومی و اجزای لودینگ در دارایی‌های بیلدشده.
 */
class PanelChromeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_list_has_an_operations_menu(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش منو',
            'slug' => 'menu-challenge',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addDays(3),
        ]);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.challenges.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-action-menu', $html);
        $this->assertStringContainsString('عملیات <svg', $html);
        $this->assertStringContainsString('action-menu-panel', $html);
        $this->assertStringContainsString('مدیریت روزها و محتوا', $html);
        $this->assertStringContainsString('حذف چالش', $html);
    }

    public function test_layouts_mark_has_js_so_cards_never_flash(): void
    {
        $this->loginAsAdmin();

        $this->assertStringContainsString("classList.add('has-js')", $this->get(route('panel.dashboard'))->assertOk()->getContent());
        $this->assertStringContainsString("classList.add('has-js')", $this->get(route('home'))->assertOk()->getContent());
    }

    public function test_public_challenge_page_has_the_loading_chip(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش چیپ',
            'slug' => 'chip-challenge',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addDays(3),
        ]);

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();
        $this->assertStringContainsString('id="page-loading-chip"', $html);
        $this->assertStringContainsString('در حال بارگذاری…', $html);
    }

    public function test_loading_pieces_are_in_built_assets(): void
    {
        $css = (string) collect(glob(public_path('build/assets/app-*.css')))->first();
        $js = (string) collect(glob(public_path('build/assets/app-*.js')))->first();
        $css = file_get_contents($css);
        $js = file_get_contents($js);

        $this->assertStringContainsString('section-loading', $css);
        $this->assertStringContainsString('page-chip', $css);
        $this->assertStringContainsString('action-menu-panel', $css);
        $this->assertStringContainsString('has-js', $css);
        $this->assertStringContainsString('initSidebarTransition', $js);
        // قرص متحرک سایدبار و منوی عملیات شناور
        $this->assertStringContainsString('sidebar-pill', $css);
        $this->assertStringContainsString('sidebar-pill', $js);
        $this->assertStringContainsString('document.fonts', $js);
        $this->assertStringContainsString('initPageReadyChip', $js);
        $this->assertStringContainsString('initActionMenus', $js);
        $this->assertStringContainsString('animationPlayState', $js);
        // انتخابگر اختصاصی به‌جای منوی پیش‌فرض مرورگر
        $this->assertStringContainsString('initCustomSelects', $js);
        $this->assertStringContainsString('csel-list', $css);
        $this->assertStringContainsString('csel-native', $css);
        // تولتیپ دقیق نمودارها
        $this->assertStringContainsString('مجموع روز', $js);
    }
}
