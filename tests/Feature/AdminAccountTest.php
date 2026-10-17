<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ساخت مدیر جدید و ورود او: تمیزکاری نام کاربری و رمز، خطاهای روشن فارسی،
 * ورود بدون حساسیت به بزرگی/کوچکی حروف و دستور ترمینالی parvane:admin.
 */
class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_admin_can_log_in_right_after_being_created(): void
    {
        $this->loginAsAdmin($this->superAdmin());

        $this->post(route('panel.admins.store'), [
            'name' => 'مریم احمدی',
            'username' => 'maryam',
            'password' => 'Maryam1234',
            'password_confirmation' => 'Maryam1234',
        ])->assertRedirect(route('panel.admins.index'));

        $this->assertDatabaseHas('admins', [
            'username' => 'maryam',
            'is_super' => false,
            'is_active' => true,
        ]);

        Auth::guard('admin')->logout();
        $this->flushSession();

        $this->post(route('panel.login.attempt'), [
            'username' => 'maryam',
            'password' => 'Maryam1234',
        ])->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs(
            Admin::query()->where('username', 'maryam')->firstOrFail(),
            'admin',
        );
    }

    public function test_login_does_not_care_about_upper_or_lower_case(): void
    {
        $this->makeChallengeAdmin(['username' => 'Maryam', 'password' => 'Maryam1234']);

        $this->post(route('panel.login.attempt'), [
            'username' => 'maryam',
            'password' => 'Maryam1234',
        ])->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticated('admin');

        // و برعکسش: نام کاربری با حرف بزرگ ساخته شده، با حرف بزرگ هم وارد می‌شود
        $this->post(route('panel.login.attempt'), [
            'username' => 'Maryam',
            'password' => 'Maryam1234',
        ])->assertRedirect(route('panel.dashboard'));
    }

    public function test_surrounding_spaces_in_the_username_and_password_are_forgiven(): void
    {
        $this->loginAsAdmin();

        $this->post(route('panel.admins.store'), [
            'name' => '  نگار کریمی  ',
            'username' => '  negar  ',
            'password' => '  Negar1234  ',
            'password_confirmation' => '  Negar1234  ',
        ])->assertRedirect(route('panel.admins.index'));

        $created = Admin::query()->where('username', 'negar')->first();

        $this->assertNotNull($created, 'نام کاربری باید بدون فاصله‌های اضافه ذخیره شود.');
        $this->assertSame('نگار کریمی', $created->name);
        $this->assertTrue(Hash::check('Negar1234', $created->password), 'رمز باید بدون فاصله‌های اضافه ذخیره شود.');

        Auth::guard('admin')->logout();
        $this->flushSession();

        $this->post(route('panel.login.attempt'), [
            'username' => ' negar ',
            'password' => 'Negar1234',
        ])->assertRedirect(route('panel.dashboard'));
    }

    public function test_a_username_that_is_not_registered_says_so(): void
    {
        $this->from(route('panel.login'))
            ->post(route('panel.login.attempt'), [
                'username' => 'kasi-ke-nist',
                'password' => 'a12345678',
            ])
            ->assertRedirect(route('panel.login'))
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString(
            'ثبت نشده است',
            $this->messageFor('username'),
        );

        $this->assertGuest('admin');
    }

    public function test_a_wrong_password_mentions_the_reset_command(): void
    {
        $this->from(route('panel.login'))
            ->post(route('panel.login.attempt'), [
                'username' => 'admin',
                'password' => 'ramz-e-eshtebah',
            ])
            ->assertRedirect(route('panel.login'))
            ->assertSessionHasErrors('username');

        $message = $this->messageFor('username');

        $this->assertStringContainsString('رمز عبور نمی‌خواند', $message);
        $this->assertStringContainsString('parvane:admin', $message);

        $this->assertGuest('admin');
    }

    public function test_only_one_failure_message_is_shown_for_a_wrong_password(): void
    {
        $response = $this->from(route('panel.login'))->post(route('panel.login.attempt'), [
            'username' => 'admin',
            'password' => 'ramz-e-eshtebah',
        ]);

        $response->assertSessionHasErrors('username');
        $response->assertSessionDoesntHaveErrors('password');

        $html = $this->followingRedirects()
            ->from(route('panel.login'))
            ->post(route('panel.login.attempt'), [
                'username' => 'admin',
                'password' => 'ramz-e-eshtebah',
            ])
            ->getContent();

        $this->assertSame(1, substr_count($html, 'رمز عبور نمی‌خواند'), 'پیام خطا باید فقط یک بار دیده شود.');

    }

    public function test_the_login_page_shows_the_currently_logged_in_admin_instead_of_redirecting(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'maryam', 'name' => 'مریم احمدی']));

        $this->get(route('panel.login'))
            ->assertOk()
            ->assertSee('هم‌اکنون با حساب «مریم احمدی» (maryam) وارد هستی', false)
            ->assertSee('خروج از این حساب')
            ->assertSee('نام کاربری');
    }

    public function test_a_logged_in_admin_can_switch_to_another_account(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'maryam', 'password' => 'Maryam1234']));

        $this->post(route('panel.login.attempt'), [
            'username' => 'admin',
            'password' => 'a12345678',
        ])->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($this->superAdmin(), 'admin');
    }

    public function test_a_username_that_only_differs_in_case_is_rejected(): void
    {
        $this->loginAsAdmin();
        $this->makeChallengeAdmin(['username' => 'maryam']);

        $this->post(route('panel.admins.store'), [
            'name' => 'مریم دوم',
            'username' => 'Maryam',
            'password' => 'Maryam1234',
            'password_confirmation' => 'Maryam1234',
        ])->assertSessionHasErrors('username');

        $this->assertSame(1, Admin::query()->whereRaw('LOWER(username) = ?', ['maryam'])->count());
    }

    public function test_the_admin_command_lists_the_admins(): void
    {
        $this->makeChallengeAdmin(['username' => 'maryam', 'name' => 'مریم احمدی']);

        $this->artisan('parvane:admin', ['--list' => true])
            ->expectsOutputToContain('maryam')
            ->expectsOutputToContain('مدیر اصلی')
            ->assertSuccessful();
    }

    public function test_the_admin_command_resets_a_password_and_activates_the_account(): void
    {
        $admin = $this->makeChallengeAdmin(['username' => 'maryam', 'password' => 'Maryam1234']);
        $admin->update(['is_active' => false]);

        $this->artisan('parvane:admin', [
            'username' => 'maryam',
            '--password' => 'RamzJadid123',
            '--active' => true,
        ])->assertSuccessful();

        $admin->refresh();

        $this->assertTrue($admin->isActive());
        $this->assertTrue(Hash::check('RamzJadid123', $admin->password));

        Auth::guard('admin')->logout();

        $this->post(route('panel.login.attempt'), [
            'username' => 'maryam',
            'password' => 'RamzJadid123',
        ])->assertRedirect(route('panel.dashboard'));
    }

    public function test_the_admin_command_creates_a_new_admin(): void
    {
        $this->artisan('parvane:admin', [
            'username' => 'negar',
            '--name' => 'نگار کریمی',
            '--password' => 'Negar1234',
        ])->assertSuccessful();

        $this->assertDatabaseHas('admins', ['username' => 'negar', 'is_super' => false, 'is_active' => true]);
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
}
