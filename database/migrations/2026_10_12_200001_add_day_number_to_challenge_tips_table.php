<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * هر نکته می‌تواند به یک روز خاص از چالش تعلق داشته باشد.
     * نکته‌های بدون روز («نکات کلی») همان‌طور که بودند باقی می‌مانند.
     */
    public function up(): void
    {
        Schema::table('challenge_tips', function (Blueprint $table) {
            $table->unsignedInteger('day_number')->nullable()->after('challenge_id');

            $table->index(['challenge_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::table('challenge_tips', function (Blueprint $table) {
            $table->dropIndex(['challenge_id', 'day_number']);
            $table->dropColumn('day_number');
        });
    }
};
