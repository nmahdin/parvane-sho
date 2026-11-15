<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_files', function (Blueprint $table) {
            $table->boolean('is_video')->default(false)->after('is_audio');
        });
    }

    public function down(): void
    {
        Schema::table('day_files', function (Blueprint $table) {
            $table->dropColumn('is_video');
        });
    }
};
