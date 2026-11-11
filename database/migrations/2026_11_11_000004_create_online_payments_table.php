<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan pembayaran online (prototype gateway) untuk pesanan atau pre-order.
     * Data kartu: HANYA merek + 4 digit terakhir. Nomor penuh & CVV tidak pernah disimpan.
     */
    public function up(): void
    {
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->morphs('payable'); // App\Models\Order | App\Models\PreOrder
            $table->enum('purpose', ['order', 'dp', 'installment', 'settlement']);
            $table->enum('method', ['qris', 'va', 'card']);
            $table->string('bank', 20)->nullable();
            $table->string('va_number', 30)->nullable();
            $table->string('card_brand', 20)->nullable();
            $table->char('card_last4', 4)->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'expired', 'refunded'])->default('pending');
            $table->dateTime('expires_at');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
    }
};
