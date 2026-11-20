<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * متن‌ها و تنظیمات پیش‌فرض سایت.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put('site_name', config('panel.site_name'));
        Setting::put('site_tagline', config('panel.site_tagline'));
        Setting::put('site_about', config('panel.site_about'));

        $this->say('تنظیمات و متن‌های سایت ثبت شد.');
    }

    /**
     * پیام وضعیت، فقط وقتی از خط فرمان اجرا می‌شود.
     */
    private function say(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
}
