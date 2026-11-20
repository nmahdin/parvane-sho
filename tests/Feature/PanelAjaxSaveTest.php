<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ذخیره بدون رفرش صفحه در پنل:
 * - فرم‌های محتوای روزها با data-ajax-save علامت خورده‌اند
 * - درخواست AJAX موفق → ریدایرکت (که JS آن را نشانه موفقیت می‌گیرد)
 * - خطای اعتبارسنجی در حالت AJAX → JSON با وضعیت 422
 * - پیام موفقیت با data-flash قابل استخراج است
 * - دارایی‌های ساخته‌شده اسپینر و توست و هندلر AJAX را دارند
 */
class PanelAjaxSaveTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش ذخیره سریع',
            'slug' => 'ajax-save',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ], $attributes));
    }

    public function test_the_day_content_forms_are_marked_for_ajax_save(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDays(2), 'ends_on' => now()->addDays(4)]);

        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار نمونه', 'sort_order' => 1]);
        $challenge->attachments()->create([
            'title' => 'فایل چالش',
            'path' => 'attachments/x.txt',
            'original_name' => 'x.txt',
            'size' => 8,
            'mime' => 'text/plain',
        ]);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'پرسش نمونه', 'is_published' => true, 'sort_order' => 1]);
        $challenge->tips()->create(['day_number' => 1, 'emoji' => '💡', 'title' => 'نکته نمونه', 'sort_order' => 1]);

        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        // همه فرم‌های محتوای روزها علامت خورده‌اند
        foreach ([
            'action="'.route('panel.days.store', $challenge).'"',
            'action="'.route('panel.days.update', [$challenge, 1]).'"',
            'action="'.route('panel.day-files.store', $challenge).'"',
            'action="'.route('panel.tips.store', $challenge).'"',
        ] as $action) {
            $form = $this->formTagFor($html, $action);
            $this->assertStringContainsString('data-ajax-save', $form, 'فرم '.$action.' باید data-ajax-save داشته باشد.');
        }

        // فرم حذف کار (با تأیید) هم AJAX است
        $task = $challenge->tasks()->firstOrFail();
        $this->assertStringContainsString(
            'data-ajax-save',
            $this->formTagFor($html, 'action="'.route('panel.tasks.destroy', [$challenge, $task]).'"')
        );

        // فرم‌های بیرون از «محتوای روزها» دست‌نخورده و بدون AJAX می‌مانند
        $this->assertStringNotContainsString(
            'data-ajax-save',
            $this->formTagFor($html, 'action="'.route('panel.attachments.store', $challenge).'"')
        );

        // بخش‌هایی که JS بعد از ذخیره جای‌گذاری می‌کند
        $this->assertStringContainsString('id="challenge-stats"', $html);
        $this->assertStringContainsString('id="tasks"', $html);
        $this->assertStringContainsString('id="tips"', $html);
        $this->assertStringContainsString('id="page-subtitle"', $html);
    }

    public function test_an_ajax_submit_redirects_and_a_validation_error_returns_422_json(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        // موفق: ریدایرکت (JS با redirect:manual آن را نشانه موفقیت می‌گیرد)
        $this->postJson(route('panel.days.store', $challenge), [
            'type' => 'task',
            'day_number' => 1,
            'title' => 'کار جدید از AJAX',
        ])->assertRedirect();

        $this->assertSame(1, $challenge->tasks()->count());
        $this->assertSame('کار جدید از AJAX', $challenge->tasks()->first()->title);

        // خطا: 422 با پیام فارسی همان فیلد
        $response = $this->postJson(route('panel.days.store', $challenge), [
            'type' => 'task',
            'day_number' => 1,
            'title' => '',
        ])->assertStatus(422);

        $this->assertSame('عنوان کار را وارد کنید.', $response->json('errors.title.0'));
    }

    public function test_the_success_flash_message_is_extractable_for_the_toast(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $this->postJson(route('panel.days.store', $challenge), [
            'type' => 'task',
            'day_number' => 2,
            'title' => 'کار روز دوم',
        ])->assertRedirect();

        // JS صفحه را دوباره می‌گیرد و پیام را از JSON فلش بیرون می‌کشد
        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('id="flash-data"', $html);
        $this->assertStringContainsString('برای روز ۲ اضافه شد', $html);
        // بنر قدیمی دیگر رندر نمی‌شود
        $this->assertStringNotContainsString('data-flash="status"', $html);
    }

    public function test_the_built_assets_ship_the_loading_and_toast_styles_and_handler(): void
    {
        $css = $this->builtAsset('css');
        $js = $this->builtAsset('js');

        // حالت لودینگ دکمه ذخیره و توست
        $this->assertStringContainsString('btn-spinner', $css);
        $this->assertStringContainsString('is-loading', $css);
        $this->assertStringContainsString('toast-success', $css);
        $this->assertStringContainsString('toast-error', $css);

        // توست و نوار آپلود با popover در لایهٔ بالای مرورگر می‌نشینند تا
        // وقتی مودال باز است، زیر پس‌زمینهٔ تار پنهان نشوند
        $this->assertStringContainsString('.toast[popover]', $css);
        $this->assertStringContainsString('.upload-progress[popover]', $css);
        $this->assertStringContainsString('showPopover', $js);

        // هندلر ذخیره بدون رفرش
        $this->assertStringContainsString('data-ajax-save', $js);
        $this->assertStringContainsString('X-Requested-With', $js);
        $this->assertStringContainsString('btn-spinner', $js);
        $this->assertStringContainsString('opaqueredirect', $js);
    }

    private function builtAsset(string $type): string
    {
        $entry = $type === 'css' ? 'resources/css/app.css' : 'resources/js/app.js';
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);

        return (string) file_get_contents(public_path('build/'.$manifest[$entry]['file']));
    }

    /**
     * تگ <form ...> مربوط به یک action مشخص از HTML صفحه.
     */
    private function formTagFor(string $html, string $actionNeedle): string
    {
        $position = strpos($html, $actionNeedle);

        $this->assertNotFalse($position, 'فرم با '.$actionNeedle.' در صفحه پیدا نشد.');

        $start = strrpos(substr($html, 0, $position), '<form');
        $end = strpos($html, '>', $position);

        return substr($html, $start, $end - $start + 1);
    }
}
