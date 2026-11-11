<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pesanan online (ready stock, ambil di toko).
     * Alur: pending_payment -> paid -> processing -> ready_pickup -> completed
     *       cabang: expired (tidak dibayar) / cancelled.
     * Data pemesan disimpan sebagai snapshot karena tamu boleh checkout tanpa akun.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30);
            $table->enum('status', ['pending_payment', 'paid', 'processing', 'ready_pickup', 'completed', 'cancelled', 'expired'])
                ->default('pending_payment');
            $table->decimal('total', 15, 2);
            $table->enum('payment_method', ['qris', 'va', 'card']);
            $table->dateTime('payment_due_at');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnUpdate()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_due_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('sub_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
