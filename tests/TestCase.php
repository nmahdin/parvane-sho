<?php

namespace Tests;

use App\Models\Admin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * مدیر اصلی سایت — همان حسابی که مهاجرت‌ها می‌سازند (admin / a12345678).
     */
    protected function superAdmin(): Admin
    {
        return Admin::query()->where('username', 'admin')->firstOrFail();
    }

    /**
     * مدیر اصلی دیگری با نام کاربری دلخواه.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeSuperAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'مدیر اصلی دوم',
            'username' => 'admin-'.Admin::query()->count(),
            'password' => 'a12345678',
            'is_super' => true,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * مدیر چالش (فقط چالش‌ها و شرکت‌کننده‌های خودش).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeChallengeAdmin(array $attributes = []): Admin
    {
        $attributes = array_merge([
            'name' => 'مدیر چالش',
            'username' => 'manager',
            'password' => 'manager-pass',
            'is_super' => false,
            'is_active' => true,
        ], $attributes);

        return Admin::query()->firstOrCreate(
            ['username' => $attributes['username']],
            $attributes
        );
    }

    /**
     * ورود به پنل در تست‌ها (بدون طی کردن فرم ورود).
     */
    protected function loginAsAdmin(?Admin $admin = null): Admin
    {
        $admin ??= $this->superAdmin();

        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
