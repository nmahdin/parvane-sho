<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * اجرای همه سیدرها: مدیران پنل، تنظیمات سایت و چالش نمونه با شرکت‌کننده‌هایش.
 *
 * php artisan migrate:fresh --seed
 * یا فقط: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingsSeeder::class,
            DemoChallengeSeeder::class,
        ]);
    }
}
