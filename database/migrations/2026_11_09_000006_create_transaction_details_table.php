<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Item per transaksi. Nama & harga produk disimpan sebagai snapshot
     * agar nota/laporan lama tidak berubah saat produk diedit/dihapus.
     */
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('qty');
            $table->decimal('purchase_price', 15, 2)->default(0); // modal saat terjual (hitung laba)
            $table->decimal('unit_price', 15, 2);
            $table->decimal('sub_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
