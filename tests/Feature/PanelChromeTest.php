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
        // قرص متحرک سایدبار حذف شده است
        $this->assertStringNotContainsString('sidebar-pill', $css);
        $this->assertStringNotContainsString('sidebar-pill', $js);
        // ناوبری چسبان دقیقاً زیر هدر چسبان می‌نشیند
        $this->assertStringContainsString('initStickyOffsets', $js);
        $this->assertStringContainsString('--sticky-nav-top', $js);
        $this->assertStringContainsString('sticky-nav', $css);
        $this->assertStringContainsString('document.fonts', $js);
        $this->assertStringContainsString('initPageReadyChip', $js);
        // پیام‌های فلش به توست تبدیل می‌شوند
        $this->assertStringContainsString('initFlashToasts', $js);
        $this->assertStringContainsString('.has-js [data-flash]{display:none}', $css);
        $this->assertStringContainsString('closeModalAnimated', $js);
        // لغو انیمیشن بسته‌شدن تا مودال دوباره نامرئی باز نشود
        $this->assertStringContainsString('getAnimations', $js);
        $this->assertStringContainsString('initActionMenus', $js);
        $this->assertStringContainsString('parentElement!==document.body', $js);
        $this->assertStringContainsString('animationPlayState', $js);
        // انتخابگر اختصاصی به‌جای منوی پیش‌فرض مرورگر
        $this->assertStringContainsString('initCustomSelects', $js);
        $this->assertStringContainsString('csel-list', $css);
        $this->assertStringContainsString('csel-native', $css);
        // تولتیپ دقیق نمودارها
        $this->assertStringContainsString('xdonut-legend', $js);
        // چک‌باکس و رادیوی اختصاصی
        $this->assertStringContainsString("input[type=checkbox]", $css);
        $this->assertStringContainsString('appearance:none', $css);
    }

    public function test_settings_page_no_longer_shows_admins_and_participant_links_sections(): void
    {
        $this->loginAsAdmin();

        $this->get(route('panel.settings.edit'))
            ->assertOk()
            ->assertDontSee('مدیران چالش‌ها')
            ->assertDontSee('لینک شرکت‌کننده‌ها');
    }

    public function test_challenge_form_offers_raised_palette_icon_tiles(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش نماد',
            'slug' => 'icon-challenge',
            'is_published' => true,
        ]);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.challenges.edit', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('emoji-tile emoji-tile-jade', $html);
        $this->assertStringContainsString('emoji-tile emoji-tile-gold', $html);
        $this->assertStringContainsString('emoji-tile emoji-tile-forest', $html);
        // نقش سفید SVG روی کاشی رنگی
        $this->assertStringContainsString('data-emoji-value="star"', $html);
        $this->assertStringContainsString('emoji-tile-jade text-white', $html);
    }

    public function test_day_modals_have_equal_size_cancel_and_submit_buttons(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش پاورقی',
            'slug' => 'footer-challenge',
            'is_published' => true,
        ]);

        $challenge->tasks()->create([
            'day_number' => 1,
            'title' => 'کار با توضیح چندخطی',
            'description' => "خط اول\n\nخط دوم",
        ]);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        // انصراف راست (اول در DOM راست‌به‌چپ)، ثبت چپ، هر دو هم‌اندازه
        $this->assertGreaterThan(1, substr_count($html, 'btn btn-ghost flex-1 justify-center'));
        // توضیح کار با حفظ خط‌های جدید نمایش داده می‌شود
        $this->assertStringContainsString('whitespace-pre-line', $html);
        $modal = substr($html, (int) strpos($html, 'id="modal-content"'));
        $this->assertLessThan(
            strpos($modal, '>ذخیره</button>'),
            strpos($modal, '>انصراف</button>')
        );
    }

    public function test_built_assets_ship_toast_position_duration_and_single_day_refresh(): void
    {
        $this->loginAsAdmin();

        $css = file_get_contents(public_path('build/'.collect(json_decode(file_get_contents(public_path('build/manifest.json')), true))
            ->first(fn ($entry) => str_contains($entry['file'] ?? '', 'app') && str_ends_with($entry['file'], '.css'))['file']));

        // توست: گوشه بالا-چپ با فاصله از حاشیه (top-5 left-5)
        $this->assertStringContainsString('top:calc(var(--spacing) * 5);left:calc(var(--spacing) * 5)', $css);
        $this->assertStringContainsString('emoji-tile', $css);

        $js = file_get_contents(public_path('build/'.collect(json_decode(file_get_contents(public_path('build/manifest.json')), true))
            ->first(fn ($entry) => str_contains($entry['file'] ?? '', 'app') && str_ends_with($entry['file'], '.js'))['file']));

        $this->assertStringContainsString('dayNumberOf', $js);
        $this->assertStringContainsString('refreshDynamicSections', $js);
        $this->assertStringContainsString('5500', $js);
    }

    public function test_custom_icon_keys_render_as_svg_not_text(): void
    {
        $challenge = Challenge::create([
            'title' => 'چالش آیکون',
            'slug' => 'svg-icon-challenge',
            'emoji' => 'star',
            'is_published' => true,
        ]);

        $this->loginAsAdmin();

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        // کلید «star» به SVG ستارهٔ سفید روی کاشی رنگی تبدیل می‌شود
        $this->assertStringContainsString('M12 3.6l2.6', $html);

        // و دیگر به‌صورت متن خام دیده نمی‌شود
        $this->assertStringNotContainsString('>star<', $html);

        // ایموجی معمولی هنوز همان ایموجی می‌ماند
        $butterfly = Challenge::create([
            'title' => 'چالش پروانه',
            'slug' => 'emoji-challenge',
            'emoji' => '🦋',
            'is_published' => true,
        ]);

        $this->get(route('panel.challenges.show', $butterfly))
            ->assertOk()
            ->assertSee('🦋');
    }
}
