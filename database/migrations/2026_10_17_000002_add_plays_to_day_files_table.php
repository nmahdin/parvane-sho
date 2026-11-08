<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ستون «plays» (شمار پخش فایل‌های صوتی) روی جدول day_files.
 *
 * عمداً در فایل مهاجرت خودش است تا جدا از ساخت جدول visits اجرا شود؛
 * گارد hasColumn هم دارد تا روی دیتابیسی که این ستون را دارد (نسخهٔ
 * قدیمی‌تر مهاجرت combined) بی‌خطر رد شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('day_files', 'plays')) {
            return;
        }

        Schema::table('day_files', function (Blueprint $table) {
            $table->unsignedInteger('plays')->default(0)->after('downloads');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('day_files', 'plays')) {
            Schema::table('day_files', function (Blueprint $table) {
                $table->dropColumn('plays');
            });
        }
    }
};
