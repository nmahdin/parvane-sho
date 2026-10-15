<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * چیدمان مرتب باکس روزها در پنل:
 * - بخش‌های هر روز (عنوان، نکته‌ها، فایل‌ها) جمع‌شو هستند
 * - فرم‌های افزودن داخل مودال‌اند (یک مودال مشترک برای همه روزها)
 * - هندلر مودال‌ها در JS بیلدشده وجود دارد
 */
class PanelDayModalsTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(): Challenge
    {
        return Challenge::create([
            'title' => 'چالش مودال‌ها',
            'slug' => 'day-modals',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addDays(6),
        ]);
    }

    private function panelHtml(Challenge $challenge): string
    {
        $this->loginAsAdmin();

        return $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
    }

    public function test_day_sections_are_collapsible_groups(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار نمونه']);
        $challenge->tips()->create(['day_number' => 1, 'title' => 'نکته نمونه', 'emoji' => '💡']);

        $html = $this->panelHtml($challenge);

        foreach (['title', 'tasks', 'questions', 'tips', 'files'] as $group) {
            $this->assertStringContainsString('id="day-1-'.$group.'"', $html);
        }

        $this->assertStringContainsString('day-group-head', $html);
    }

    public function test_add_forms_live_in_shared_modals_with_a_day_input(): void
    {
        $challenge = $this->makeChallenge();

        $html = $this->panelHtml($challenge);

        foreach (['modal-content', 'modal-bulk', 'modal-file', 'modal-tip'] as $modal) {
            $this->assertStringContainsString('<dialog id="'.$modal.'"', $html);
        }

        // فرم‌های افزودن داخل مودال‌اند و روز از پیش‌انتخاب‌شده می‌گیرند
        $this->assertStringContainsString('data-modal-day-input', $html);
        $this->assertStringContainsString('data-open-modal="modal-file"', $html);
        $this->assertStringContainsString('data-modal-type="question"', $html);

        // دکمه و باکس افزودن از بالای فهرست روزها حذف شده است
        $this->assertStringNotContainsString('+ افزودن کار یا پرسش</button>', $html);

        // مودال وسط صفحه می‌نشیند (preflight حاشیه dialog را صفر می‌کند)
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        $this->assertMatchesRegularExpression('/\.panel-modal\{[^}]*position:fixed[^}]*\}/', $css);
        $this->assertMatchesRegularExpression('/\.panel-modal\{[^}]*margin:auto[^}]*\}/', $css);

        // فرم فایل مودال، AJAX و multipart است
        $modal = substr($html, (int) strpos($html, '<dialog id="modal-file"'));
        $modal = substr($modal, 0, (int) strpos($modal, '</dialog>'));

        $this->assertStringContainsString('data-ajax-save', $modal);
        $this->assertStringContainsString('enctype="multipart/form-data"', $modal);
        $this->assertStringContainsString('type="file"', $modal);
    }

    public function test_the_modal_handler_is_in_the_built_js(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $js = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));

        $this->assertStringContainsString('initModals', $js);
        $this->assertStringContainsString('data-open-modal', $js);
        $this->assertStringContainsString('showModal', $js);
        $this->assertStringContainsString('closeModalFor', $js);
    }

    public function test_the_modal_styles_are_in_the_built_css(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));

        $this->assertStringContainsString('.panel-modal', $css);
        $this->assertStringContainsString('.day-group-head', $css);

        // مودال وسط صفحه می‌چسبد (position/margin در CSS ساخته‌شده)
        $modalCss = substr($css, (int) strpos($css, '.panel-modal{'));
        $modalCss = substr($modalCss, 0, (int) strpos($modalCss, '}'));
        $this->assertStringContainsString('position:fixed', $modalCss);
        $this->assertStringContainsString('inset:0', $modalCss);
        $this->assertStringContainsString('margin:auto', $modalCss);
    }
}
