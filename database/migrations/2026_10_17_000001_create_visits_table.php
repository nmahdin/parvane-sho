<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اگر قبلاً (مثلاً با نسخهٔ قدیمی همین مهاجرت) ساخته شده، دوباره نساز
        if (Schema::hasTable('visits')) {
            return;
        }

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->cascadeOnDelete();
            // home | challenge | day | dashboard
            $table->string('type', 20);
            $table->unsignedSmallInteger('day_number')->nullable();
            $table->timestamp('visited_at');

            $table->index(['challenge_id', 'visited_at']);
            $table->index(['type', 'visited_at']);
            $table->index('visited_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
