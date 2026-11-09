<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaksi penjualan. ID = nomor faktur (format YYYYMMDD0001), bukan auto increment.
     * Tidak ada lagi `table_id` (fitur meja dihapus).
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->date('date');
            // nullOnDelete: riwayat penjualan tetap ada walau pelanggan/kasir dihapus
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->enum('payment_method', ['cash', 'debit', 'qris', 'transfer'])->default('cash');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
