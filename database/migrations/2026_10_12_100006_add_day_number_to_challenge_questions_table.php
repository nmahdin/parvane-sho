<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * هر پرسش هم مثل کارها به یک روز (روز چالش) وابسته می‌شود؛
     * پس در بخش «روزها و کارها» کنار کارهای همان روز نمایش داده می‌شود.
     */
    public function up(): void
    {
        Schema::table('challenge_questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('day_number')->nullable()->after('challenge_id');

            $table->index(['challenge_id', 'day_number', 'sort_order'], 'challenge_questions_day_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_questions', function (Blueprint $table) {
            $table->dropIndex('challenge_questions_day_order_index');
            $table->dropColumn('day_number');
        });
    }
};
