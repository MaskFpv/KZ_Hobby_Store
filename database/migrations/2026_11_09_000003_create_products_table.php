<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog barang hobi. Pengganti tabel `menus`.
     * Stok disimpan langsung di kolom `stock` (pengganti tabel `stocks`).
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // restrict: kategori/brand tidak bisa dihapus selama masih dipakai produk
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            // decimal(15,2): harga barang hobi bisa jutaan rupiah
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->enum('condition', ['new', 'second'])->default('new');      // Baru (MIB) / Second (BIB)
            $table->enum('type', ['ready_stock', 'pre_order'])->default('ready_stock');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
