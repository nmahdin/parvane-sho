<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('day_files', function (Blueprint $table) {
            // پخش‌های فایل صوتی (جدا از شمار دانلودها)
            $table->unsignedInteger('plays')->default(0)->after('downloads');
        });
    }

    public function down(): void
    {
        Schema::table('day_files', function (Blueprint $table) {
            $table->dropColumn('plays');
        });

        Schema::dropIfExists('visits');
    }
};
