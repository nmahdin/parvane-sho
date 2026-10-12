<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * اجرای همه سیدرها: تنظیمات سایت و چالش نمونه با شرکت‌کننده‌هایش.
 *
 * php artisan migrate:fresh --seed
 * یا فقط: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            DemoChallengeSeeder::class,
        ]);
    }
}
