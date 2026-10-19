<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تعهدنامه‌ای که شرکت‌کننده در اولین ورود با لینک اختصاصی‌اش می‌بیند و
     * باید بپذیرد تا داشبوردش باز شود.
     */
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->boolean('pledge_enabled')->default(false)->after('daily_release');
            $table->string('pledge_title', 190)->nullable()->after('pledge_enabled');
            $table->text('pledge_text')->nullable()->after('pledge_title');
        });

        // چالش‌هایی که از قبل ساخته شده‌اند هم تعهدنامه را نشان می‌دهند
        // (با متن پیش‌فرض)؛ از صفحه ویرایش هر چالش می‌شود خاموشش کرد.
        DB::table('challenges')->update(['pledge_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn(['pledge_enabled', 'pledge_title', 'pledge_text']);
        });
    }
};
