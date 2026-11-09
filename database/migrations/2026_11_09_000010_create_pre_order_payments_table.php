<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat pembayaran Pre-Order: DP, cicilan, pelunasan.
     */
    public function up(): void
    {
        Schema::create('pre_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->enum('type', ['dp', 'installment', 'settlement']);
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'debit', 'qris', 'transfer'])->default('cash');
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_order_payments');
    }
};
