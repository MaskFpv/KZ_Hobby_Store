<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stok masuk / restock dari supplier atau importir.
     * Stok produk baru bertambah saat status berubah menjadi `received`.
     */
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('supplier')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->enum('status', ['ordered', 'shipped', 'received', 'cancelled'])->default('ordered');
            $table->date('ordered_at');
            $table->dateTime('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
