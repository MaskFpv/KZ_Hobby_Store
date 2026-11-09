<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pre-Order barang yang belum rilis. Pembayaran (DP/cicilan/pelunasan)
     * dicatat di `pre_order_payments`; `paid_amount` = total pembayaran masuk.
     */
    public function up(): void
    {
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('order_date');
            $table->date('estimated_arrival')->nullable();
            // waiting = menunggu barang, arrived = barang tiba, completed = lunas & diambil
            $table->enum('status', ['waiting', 'arrived', 'completed', 'cancelled'])->default('waiting');
            // diisi saat pelunasan: PO dicatat sebagai transaksi penjualan
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_orders');
    }
};
