<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * چند مدیر با نام کاربری و رمز عبور اختصاصی.
     * هر چالش یک مدیر دارد؛ مدیر اصلی (admin) همه چیز را می‌بیند.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('is_super')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $superId = $this->primaryAdminId();

        // چالش‌های موجود به مدیر اصلی می‌رسند
        DB::table('challenges')->whereNull('admin_id')->update(['admin_id' => $superId]);
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
        });

        Schema::dropIfExists('admins');
    }

    /**
     * مدیر اصلی سایت (admin / a12345678) را می‌سازد و شناسه‌اش را برمی‌گرداند.
     */
    private function primaryAdminId(): int
    {
        $existing = DB::table('admins')->where('username', 'admin')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('admins')->insertGetId([
            'name' => 'مدیر اصلی',
            'username' => 'admin',
            'password' => Hash::make('a12345678'),
            'is_super' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
