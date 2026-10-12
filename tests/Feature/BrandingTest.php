<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * نشان پروانه سبز (لوگو): خودِ پروانه سبز است، پس پس‌زمینه‌اش هیچ‌جا سبز
 * نیست؛ همه‌جا چیپ سفید با حاشیه روشن است — هم در نوار کناری پنل، هم در
 * هدر و پابرگ سایت، هم در صفحه ورود به پنل و صفحه‌های خطا.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * آیا این تکه HTML نشان پروانه را با پس‌زمینه سبز نشان می‌دهد؟
     */
    private function hasGreenLogoBackground(string $html): bool
    {
        preg_match_all('/<(?:img|span)[^>]*favicon\.svg[^>]*>|<span[^>]*>\s*<img[^>]*favicon\.svg[^>]*>/s', $html, $matches);

        foreach ($matches[0] as $snippet) {
            if (preg_match('/from-forest|to-leaf|from-jade|bg-forest|bg-jade|bg-leaf|bg-white\/\d/', $snippet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * تکه‌های HTML که نشان پروانه در آن‌ها آمده است.
     *
     * @return list<string>
     */
    private function logoMarkup(string $html): array
    {
        preg_match_all('/<(?:img|span)[^>]*favicon\.svg[^>]*>|<span[^>]*>\s*<img[^>]*favicon\.svg[^>]*>/s', $html, $matches);

        return $matches[0];
    }

    private function makeChallenge(): Challenge
    {
        return Challenge::create([
            'title' => 'چالش پروانه',
            'slug' => 'parvane-logo',
            'is_published' => true,
            'starts_on' => today(),
        ]);
    }

    public function test_the_public_header_and_footer_show_the_butterfly_on_a_white_chip(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $logos = $this->logoMarkup($html);

        $this->assertNotEmpty($logos, 'نشان پروانه در هدر/پابرگ سایت پیدا نشد.');
        $this->assertFalse($this->hasGreenLogoBackground($html), 'پس‌زمینه نشان پروانه در سایت هنوز سبز است.');
        $this->assertStringContainsString('bg-white', $logos[0]);
    }

    public function test_the_panel_sidebar_shows_the_butterfly_on_a_white_chip(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        $html = $this->get(route('panel.dashboard'))->assertOk()->getContent();

        $logos = $this->logoMarkup($html);

        $this->assertNotEmpty($logos, 'نشان پروانه در نوار کناری پنل پیدا نشد.');
        $this->assertFalse($this->hasGreenLogoBackground($html), 'پس‌زمینه نشان پروانه در پنل هنوز سبز است.');
        $this->assertStringContainsString('bg-white', $logos[0]);
    }

    public function test_the_login_page_shows_the_butterfly_on_a_white_chip(): void
    {
        $html = $this->get(route('panel.login'))->assertOk()->getContent();

        $this->assertFalse($this->hasGreenLogoBackground($html), 'پس‌زمینه نشان پروانه در صفحه ورود سبز است.');

        // چیپ سفید دور نشان (قبلاً bg-white/15 سبزِ نیمه‌شفاف بود)
        $this->assertStringContainsString('rounded-3xl bg-white shadow-lg', $html);
        $this->assertStringNotContainsString('bg-white/15', $html);
    }

    public function test_the_error_page_shows_the_butterfly_on_a_white_chip(): void
    {
        $html = $this->get('/this-page-does-not-exist')->assertNotFound()->getContent();

        $logos = $this->logoMarkup($html);

        $this->assertNotEmpty($logos, 'نشان پروانه در صفحه خطا پیدا نشد.');
        $this->assertFalse($this->hasGreenLogoBackground($html), 'پس‌زمینه نشان پروانه در صفحه خطا سبز است.');
    }

    public function test_the_participant_and_public_challenge_pages_keep_the_logo_light(): void
    {
        $challenge = $this->makeChallenge();
        $participant = Participant::create(['challenge_id' => $challenge->id, 'name' => 'زهرا']);

        foreach ([$challenge->publicUrl(), route('participant.show', $participant)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertFalse($this->hasGreenLogoBackground($html), 'پس‌زمینه نشان پروانه در '.$url.' سبز است.');
        }
    }
}
