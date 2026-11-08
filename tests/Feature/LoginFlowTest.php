<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ورود به پنل: پیام‌های روشن فارسی، مدیریت حالتی که دیتابیس آماده نیست،
 * کوکی سشن درست پشت پروکسی https و صفحه‌های خطای راهنما.
 */
class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_the_default_credentials_works(): void
    {
        $this->post(route('panel.login.attempt'), [
            'username' => 'admin',
            'password' => 'a12345678',
        ])
            ->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($this->superAdmin(), 'admin');
    }

    public function test_a_wrong_password_shows_a_persian_error(): void
    {
        $this->from(route('panel.login'))
            ->post(route('panel.login.attempt'), [
                'username' => 'admin',
                'password' => 'not-the-password',
            ])
            ->assertRedirect(route('panel.login'))
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('رمز عبور', $this->messageFor('username'));
        $this->assertGuest('admin');
    }

    public function test_a_deactivated_admin_cannot_log_in(): void
    {
        $admin = $this->superAdmin();
        $admin->update(['is_active' => false]);

        $this->from(route('panel.login'))
            ->post(route('panel.login.attempt'), [
                'username' => $admin->username,
                'password' => 'a12345678',
            ])
            ->assertSessionHasErrors(['username' => 'حساب شما غیرفعال است. با مدیر اصلی تماس بگیرید.']);

        $this->assertGuest('admin');
    }

    public function test_the_login_page_says_what_to_do_when_no_admin_exists_yet(): void
    {
        Admin::query()->delete();

        $this->get(route('panel.login'))
            ->assertOk()
            ->assertSee('دیتابیس پنل هنوز آماده نیست')
            ->assertSee('php artisan migrate --seed');
    }

    public function test_login_without_any_admin_reports_the_problem_instead_of_crashing(): void
    {
        Admin::query()->delete();

        $this->from(route('panel.login'))
            ->post(route('panel.login.attempt'), [
                'username' => 'admin',
                'password' => 'a12345678',
            ])
            ->assertRedirect(route('panel.login'))
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('migrate', $this->messageFor('username'));
    }

    /**
     * پیام خطای یک فیلد، هم در حالت ErrorBag و هم در حالت آرایه.
     */
    private function messageFor(string $key): string
    {
        $errors = session('errors');

        if (is_array($errors)) {
            return implode(' ', $errors[$key] ?? []);
        }

        return (string) $errors->first($key);
    }

    public function test_the_login_page_survives_a_missing_admins_table(): void
    {
        Schema::drop('admins');

        $this->get(route('panel.login'))
            ->assertOk()
            ->assertSee('دیتابیس پنل هنوز آماده نیست');
    }

    public function test_secure_requests_get_a_cross_site_friendly_session_cookie(): void
    {
        // پشت پروکسی https (مثل پیش‌نمایش ابری یا iframe)
        $response = $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'demo.e2b.app',
        ])->get(route('panel.login'))->assertOk();

        $session = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_contains($cookie->getName(), 'session'));

        $this->assertNotNull($session, 'کوکی سشن ست نشده است.');
        $this->assertTrue($session->isSecure(), 'کوکی روی https باید Secure باشد.');
        $this->assertSame('none', $session->getSameSite(), 'کوکی داخل iframe باید SameSite=None باشد.');
    }

    public function test_local_http_requests_keep_the_default_cookie_policy(): void
    {
        $response = $this->get(route('panel.login'))->assertOk();

        $session = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_contains($cookie->getName(), 'session'));

        $this->assertNotNull($session);
        $this->assertFalse($session->isSecure(), 'روی http محلی کوکی نباید Secure باشد.');
        $this->assertSame('lax', $session->getSameSite());
    }

    public function test_the_expired_page_is_persian_and_points_to_the_login(): void
    {
        $this->get('/test-expired-419')
            ->assertStatus(419)
            ->assertSee('صفحه منقضی شد')
            ->assertSee(route('panel.login'), false);
    }

    public function test_the_forbidden_page_is_persian_and_points_to_the_panel(): void
    {
        $this->get('/test-forbidden-403')
            ->assertStatus(403)
            ->assertSee('به این بخش دسترسی نداری')
            ->assertSee(route('panel.dashboard'), false);
    }

    public function test_the_not_found_page_is_persian(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertStatus(404)
            ->assertSee('این صفحه پیدا نشد');
    }

    public function test_the_setup_command_builds_the_super_admin_on_an_empty_database(): void
    {
        Admin::query()->delete();

        $this->artisan('parvane:setup')
            ->expectsOutputToContain('admin')
            ->assertSuccessful();

        $admin = Admin::query()->where('username', 'admin')->first();

        $this->assertNotNull($admin, 'دستور parvane:setup باید مدیر اصلی را بسازد.');
        $this->assertTrue($admin->isSuper());
        $this->assertTrue($admin->isActive());
        $this->assertTrue(Hash::check('a12345678', $admin->password));
    }

    public function test_the_setup_command_can_also_create_the_demo_data(): void
    {
        Admin::query()->delete();

        $this->artisan('parvane:setup', ['--demo' => true])->assertSuccessful();

        $this->assertSame(1, Challenge::query()->count());
        $this->assertSame(3, Challenge::query()->firstOrFail()->participants()->count());
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/test-expired-419', fn () => abort(419));
        Route::middleware('web')->get('/test-forbidden-403', fn () => abort(403));
    }
}
