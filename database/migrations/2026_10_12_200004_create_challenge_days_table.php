<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تنظیمات هر روز از چالش: عنوان و توضیحات.
     * خودِ روزها از تاریخ شروع و پایان چالش ساخته می‌شوند و به صورت پیش‌فرض
     * فعال‌اند؛ رکورد هر روز فقط وقتی ساخته می‌شود که مدیر برای آن روز
     * عنوان یا توضیحی بگذارد.
     */
    public function up(): void
    {
        Schema::create('challenge_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_number');
            $table->string('title', 190)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['challenge_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_days');
    }
};
