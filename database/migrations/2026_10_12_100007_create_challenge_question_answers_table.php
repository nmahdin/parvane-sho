<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * پاسخ شرکت‌کننده‌ها به پرسش‌های چالش.
     * هر شرکت‌کننده برای هر پرسش یک پاسخ دارد (قابل ویرایش).
     */
    public function up(): void
    {
        Schema::create('challenge_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->unique(['challenge_question_id', 'participant_id'], 'question_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_question_answers');
    }
};
