<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * بهینه‌بودن سایت برای موبایل: ناوبری پایین پنل، جدول‌هایی که روی گوشی
 * به کارت تبدیل می‌شوند، فیلدهای ۱۶ پیکسلی و دکمه‌های در دسترس.
 */
class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Challenge, 1: Participant}
     */
    private function makeChallenge(): array
    {
        $challenge = Challenge::create([
            'title' => 'چالش موبایل',
            'slug' => 'mobile-challenge',
            'is_published' => true,
            'starts_on' => today(),
        ]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار روز اول']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش روز اول']);

        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی']);

        return [$challenge, $participant];
    }

    public function test_the_panel_has_a_bottom_navigation_for_phones(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        $html = $this->get(route('panel.dashboard'))->assertOk()->getContent();

        // نوار پایین صفحه برای موبایل
        $this->assertStringContainsString('fixed inset-x-0 bottom-0', $html);
        $this->assertStringContainsString('safe-bottom', $html);

        $start = strpos($html, 'fixed inset-x-0 bottom-0');
        $end = strpos($html, '</nav>', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $bottomNav = substr($html, $start, $end - $start);

        foreach ([
            route('panel.dashboard'),
            route('panel.challenges.index'),
            route('panel.participants.index'),
            route('panel.settings.edit'),
            route('panel.admins.index'),
        ] as $url) {
            $this->assertStringContainsString($url, $bottomNav, 'لینک '.$url.' در نوار پایین موبایل نیست.');
        }

        // نوار قدیمی افقی حذف شده و فاصله پایین صفحه برای نوار جدید گذاشته شده
        $this->assertStringNotContainsString('flex gap-2 overflow-x-auto pb-1', $html);
        $this->assertStringContainsString('pb-28', $html);
    }

    public function test_every_panel_table_has_a_card_mode_for_phones(): void
    {
        $tables = 0;

        foreach ($this->panelViewFiles() as $file) {
            $markup = File::get($file);

            foreach (explode('<table class="table table-cards">', $markup) as $index => $part) {
                if ($index === 0) {
                    continue; // قبل از اولین جدول
                }

                $tables++;
                $block = substr($part, 0, (int) strpos($part, '</table>'));

                $cells = substr_count($block, '<td');
                $labels = substr_count($block, 'data-label="');

                $this->assertSame(
                    $cells,
                    $labels,
                    basename($file).': هر سلول جدول باید data-label داشته باشد تا روی موبایل کارت درست بسازد.'
                );
            }

            // هیچ جدولی بدون حالت کارت روی موبایل نمانَد
            $this->assertSame(
                substr_count($markup, '<table class="table">'),
                0,
                basename($file).': جدولی هست که کلاس table-cards ندارد.'
            );
        }

        $this->assertGreaterThanOrEqual(5, $tables, 'همه جدول‌های پنل باید حالت کارت داشته باشند.');
    }

    public function test_the_rendered_panel_page_outputs_the_card_mode(): void
    {
        $this->loginAsAdmin($this->superAdmin());
        $this->makeChallenge();

        $html = $this->get(route('panel.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('<table class="table table-cards">', $html);
        $this->assertStringContainsString('data-label="پیشرفت"', $html);
    }

    public function test_the_mobile_styles_are_shipped_in_the_built_css(): void
    {
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);

        $entry = $manifest['resources/css/app.css']['file'] ?? null;
        $this->assertNotNull($entry, 'فایل CSS ساخته‌شده در manifest نیست.');

        $css = File::get(public_path('build/'.$entry));

        $this->assertStringContainsString('table-cards', $css, 'حالت کارتی جدول‌ها به CSS نرسیده است.');
        $this->assertStringContainsString('safe-bottom', $css, 'فاصله پایین صفحه برای موبایل در CSS نیست.');
        $this->assertStringContainsString('767px', $css, 'قواعد موبایل در CSS نیست.');
        $this->assertStringContainsString('min-height:2.75rem', $css, 'اندازه دکمه‌ها برای لمس مناسب نشده است.');
    }

    public function test_the_public_header_has_a_phone_shortcut_to_the_personal_code(): void
    {
        $this->makeChallenge();

        $html = $this->get(route('home'))->assertOk()->getContent();

        // دکمه کوتاه «کد من» کنار منوی همبرگری روی موبایل
        $this->assertStringContainsString('کد من', $html);
        $this->assertStringContainsString('data-menu-toggle="#mobile-menu"', $html);
    }

    public function test_the_participant_dashboard_is_usable_on_a_phone(): void
    {
        [$challenge, $participant] = $this->makeChallenge();

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();

        // تیک زدن کارها: کل ردیف دکمه است و تمام‌عرض
        $this->assertStringContainsString('flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-right', $html);

        // دکمه ثبت پاسخ روی موبایل تمام‌عرض است
        $this->assertStringContainsString('btn btn-primary btn-sm w-full sm:w-auto', $html);

        // سرصفحه روی موبایل جمع‌وجورتر باز می‌شود
        $this->assertStringContainsString('pt-6 sm:px-6 sm:pt-12', $html);

        // پرسش‌ها با منوی بازشو تا صفحه شلوغ نشود
        $this->assertStringContainsString('<details id="question-', $html);

        // متا ویوپورت برای موبایل
        $this->assertStringContainsString('width=device-width, initial-scale=1', $html);
    }

    public function test_the_attachments_box_comes_first_on_phones(): void
    {
        [$challenge] = $this->makeChallenge();

        $challenge->attachments()->create([
            'title' => 'راهنمای چالش',
            'path' => 'attachments/guide.txt',
            'original_name' => 'guide.txt',
            'size' => 2048,
            'mime' => 'text/plain',
        ]);

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();

        // دو نسخه: یکی برای موبایل بالای صفحه، یکی برای دسکتاپ در ستون کنار
        $this->assertSame(2, substr_count($html, 'راهنمای چالش'));

        $mobile = strpos($html, 'class="card lg:hidden"');
        $desktop = strpos($html, 'class="card hidden lg:block"');
        $days = strpos($html, 'روزها، کارها و پرسش‌ها');

        $this->assertNotFalse($mobile, 'نسخه موبایل باکس فایل‌ها پیدا نشد.');
        $this->assertNotFalse($desktop, 'نسخه دسکتاپ باکس فایل‌ها پیدا نشد.');
        $this->assertNotFalse($days);

        $this->assertLessThan($days, $mobile, 'در موبایل باکس فایل‌های ضمیمه باید بالای روزها و کارها بیاید.');
        $this->assertGreaterThan($days, $desktop, 'در دسکتاپ باکس فایل‌ها باید در ستون کنار و پایین محتوا بماند.');
    }

    public function test_the_public_challenge_page_keeps_day_cards_tight_on_phones(): void
    {
        [$challenge] = $this->makeChallenge();

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('px-4 py-3.5 sm:px-6 sm:py-4', $html);
        $this->assertStringContainsString('width=device-width, initial-scale=1', $html);
    }

    /**
     * @return list<string>
     */
    private function panelViewFiles(): array
    {
        $files = array_merge(
            glob(resource_path('views/panel/*.blade.php')) ?: [],
            glob(resource_path('views/panel/*/*.blade.php')) ?: [],
        );

        return array_values(array_filter($files, fn (string $file) => str_contains(File::get($file), '<table')));
    }
}
