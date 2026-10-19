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

        // فلش کنار آیتم فعال سایدبار حذف شده است
        $this->assertStringNotContainsString('◂', $html);

        // پس‌زمینه پنل توکن shell (#EFF1E6) است و باکس‌ها surface بدون حاشیه
        $this->assertStringContainsString('<body class="min-h-screen bg-shell">', $html);
        $this->assertStringContainsString('surface', $html);

        // هدر اصلی همیشه چسبان است و ناوبری بخش‌ها زیر آن فیکس می‌شود
        $this->assertStringNotContainsString('lg:static', $html);
        // ناوبری بالاتر از هدر نقاشی می‌شود تا سایهٔ هدر رویش نیفتد
        $this->assertStringContainsString('sticky top-[4.5rem] z-30', $html);
        $this->assertStringContainsString('sticky top-0 z-20', $html);

        // همه گروه‌های داخل روز به‌طور پیش‌فرض بسته‌اند (بدون open)
        foreach (['title', 'tasks', 'questions', 'tips', 'files'] as $group) {
            $this->assertStringNotContainsString('id="day-1-'.$group.'" class="day-group rounded-2xl bg-cream/70" open', $html);
            $this->assertStringNotContainsString('id="day-1-'.$group.'" class="day-group rounded-2xl bg-paper/80" open', $html);
        }
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
        $this->assertMatchesRegularExpression('/\.panel-modal\[open\]\{[^}]*place-items:center[^}]*\}/', $css);

        // فرم فایل مودال، AJAX و multipart است
        $modal = substr($html, (int) strpos($html, '<dialog id="modal-file"'));
        $modal = substr($modal, 0, (int) strpos($modal, '</dialog>'));

        $this->assertStringContainsString('data-ajax-save', $modal);
        $this->assertStringContainsString('enctype="multipart/form-data"', $modal);
        $this->assertStringContainsString('type="file"', $modal);
    }

    public function test_each_day_has_four_add_buttons_and_modals_stay_open(): void
    {
        $challenge = $this->makeChallenge();

        $html = $this->panelHtml($challenge);

        // چهار دکمه ثابت بالای باکس هر روز؛ هر کدام مودال خودش را باز می‌کند
        $this->assertStringContainsString('📝 افزودن کار (چالش عملی)</button>', $html);
        $this->assertStringContainsString('❓ افزودن پرسش</button>', $html);
        $this->assertStringContainsString('💡 افزودن نکته</button>', $html);
        $this->assertStringContainsString('📎 افزودن فایل</button>', $html);

        // عنوان مودال از دکمه می‌آید و سوییچ «کار یا پرسش» فقط بدون نوع دیده می‌شود
        $this->assertStringContainsString('data-modal-heading', $html);
        $this->assertStringContainsString('data-day-type-switch', $html);
        $this->assertStringContainsString('data-modal-title="افزودن پرسش"', $html);

        // بعد از ذخیره، فرم خالی می‌شود و مودال بسته نمی‌شود:
        // هر چهار فرم مودال data-stay-open دارند (با فاصلهٔ اول تا attribute-bag
        // کامپوننت نکته که data-stay-open="data-stay-open" رندر می‌شود شمرده نشود)
        $this->assertSame(4, substr_count($html, ' data-stay-open'));

        // مودال نکته فیلد انتخاب روز ندارد؛ روز از دکمهٔ همان روز می‌آید
        $tipModal = substr($html, (int) strpos($html, '<dialog id="modal-tip"'));
        $tipModal = substr($tipModal, 0, (int) strpos($tipModal, '</dialog>'));
        $this->assertStringNotContainsString('<select', $tipModal);
        $this->assertStringContainsString('data-modal-day-input', $tipModal);
    }

    public function test_the_modal_handler_is_in_the_built_js(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $js = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));

        $this->assertStringContainsString('initModals', $js);
        $this->assertStringContainsString('data-open-modal', $js);
        $this->assertStringContainsString('showModal', $js);
        $this->assertStringContainsString('closeModalFor', $js);
        // مودال بعد از ذخیره باز می‌ماند و فرم خالی می‌شود
        $this->assertStringContainsString('data-stay-open', $js);
        $this->assertStringContainsString('resetModalForm', $js);
        $this->assertStringContainsString('data-modal-heading', $js);
        // انیمیشن ورود کارت‌ها
        $this->assertStringContainsString('initCardEntrance', $js);
        $this->assertStringContainsString('animationDelay', $js);
        $this->assertStringContainsString('.card, .day-card, .day-group, .surface', $js);
        // انیمیشن نرم باز/بسته شدن کارت روزها
        $this->assertStringContainsString('initDayCardAnimation', $js);
        // نوار بارگذاری هنگام جابه‌جایی بین صفحات
        $this->assertStringContainsString('initPageLoader', $js);
        $this->assertStringContainsString('details.day-card', $js);
    }

    public function test_the_modal_styles_are_in_the_built_css(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));

        $this->assertStringContainsString('.panel-modal', $css);
        $this->assertStringContainsString('.day-group-head', $css);

        // مودال دقیقاً وسط صفحه می‌نشیند: dialog پوشش تمام‌صفحه می‌شود و در
        // حالت باز با گرید (display:grid + place-items:center) هم افقی و هم
        // عمودی مرکزچین می‌شود — بدون تکیه بر رفتار مرورگرها
        $modalCss = substr($css, (int) strpos($css, '.panel-modal{'));
        $modalCss = substr($modalCss, 0, (int) strpos($modalCss, '}'));
        $this->assertStringContainsString('position:fixed', $modalCss);
        $this->assertStringContainsString('inset:0', $modalCss);
        $this->assertStringContainsString('height:100%', $modalCss);

        $openCss = substr($css, (int) strpos($css, '.panel-modal[open]{'));
        $openCss = substr($openCss, 0, (int) strpos($openCss, '}'));
        $this->assertStringContainsString('display:grid', $openCss);
        $this->assertStringContainsString('place-items:center', $openCss);

        // فاصله سرِ کارت روز تا اولین بخش محتوا (نکته‌ها) در صفحه چالش
        $dayBodyCss = substr($css, (int) strpos($css, '.day-body{'));
        $dayBodyCss = substr($dayBodyCss, 0, (int) strpos($dayBodyCss, '}'));
        $this->assertStringContainsString('padding-top', $dayBodyCss);

        // کارت‌ها بدون حاشیه‌اند، سایه دارند و با انیمیشن ورود می‌آیند
        $this->assertStringContainsString('.card{box-shadow:', $css);
        $this->assertStringContainsString('card-in', $css);
        $this->assertSame(0, preg_match('/\.card\{[^}]*ring-line[^}]*\}/', $css));

        // پالت تازه: cream = #F6F6F2، پس‌زمینه پنل = shell #EFF1E6
        $this->assertStringContainsString('#f6f6f2', strtolower($css));
        $this->assertStringContainsString('#eff1e6', strtolower($css));

        // سایدبار سفید و آیتم فعال #F6F6F2
        $this->assertSame(1, preg_match('/\.panel-sidebar\{[^}]*background-color:var\(--color-paper\)/', $css));
        $this->assertSame(1, preg_match('/\.sidebar-link\.is-active\{[^}]*background-color:var\(--color-jade\)/', $css));

        // پخش‌کنندهٔ صوت سفید است
        $this->assertSame(1, preg_match('/\.audio-player\{[^}]*background-color:var\(--color-paper\)/', $css));

        // نوار بارگذاری صفحه
        $this->assertStringContainsString('page-loader', $css);

        // پالت پررنگ‌تر و حذف نوار کناری ریل‌ها
        $this->assertStringContainsString('#b8c9a3', strtolower($css));
        $this->assertStringContainsString('#f2dda8', strtolower($css));
        $this->assertSame(0, preg_match('/\.rail\{[^}]*border-inline-start/', $css));

        // آیتم فعال سایدبار: برجسته با سایه، بدون حاشیه
        $activeCss = substr($css, (int) strpos($css, '.sidebar-link.is-active{'));
        $activeCss = substr($activeCss, 0, (int) strpos($activeCss, '}'));
        $this->assertStringContainsString('box-shadow', $activeCss);
        $this->assertStringNotContainsString('ring', $activeCss);
    }
}
