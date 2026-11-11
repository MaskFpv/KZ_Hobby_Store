<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun website untuk member. Member yang dibuat kasir di toko tetap boleh
     * tanpa password (belum punya akun web).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('alamat');
            $table->rememberToken()->after('password');
            $table->timestamp('terms_accepted_at')->nullable()->after('remember_token');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['password', 'remember_token', 'terms_accepted_at']);
        });
    }
};
