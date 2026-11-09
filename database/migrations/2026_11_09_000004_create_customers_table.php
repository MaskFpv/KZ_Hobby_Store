<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pelanggan / member kolektor. Kolom lama (nama, email, alamat) dipertahankan.
     * `kode_member` diisi otomatis oleh model Customer.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('kode_member', 20)->unique();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
