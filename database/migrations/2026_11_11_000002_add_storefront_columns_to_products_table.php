<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom untuk website pelanggan:
     * - is_published   : tampil di website
     * - is_featured    : tampil di hero beranda
     * - po_deadline    : batas pemesanan PO
     * - release_estimate: perkiraan rilis/tiba, teks bebas (mis. "Mei 2027")
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('type');
            $table->boolean('is_featured')->default(false)->after('is_published');
            $table->date('po_deadline')->nullable()->after('is_featured');
            $table->string('release_estimate', 50)->nullable()->after('po_deadline');

            $table->index(['is_published', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'type']);
            $table->dropColumn(['is_published', 'is_featured', 'po_deadline', 'release_estimate']);
        });
    }
};
