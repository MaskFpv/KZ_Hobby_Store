<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dukungan penjualan online:
     * - transactions.channel (pos/online) + metode bayar va & card
     * - pre_orders.channel (store/online) + status pending_payment (menunggu DP online)
     * - pre_order_payments: metode bayar va & card
     * Mengubah enum memakai SQL MySQL langsung (tanpa doctrine/dbal).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY payment_method ENUM('cash','debit','qris','transfer','va','card') NOT NULL DEFAULT 'cash'");
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('channel', ['pos', 'online'])->default('pos')->after('payment_method');
        });

        DB::statement("ALTER TABLE pre_orders MODIFY status ENUM('pending_payment','waiting','arrived','completed','cancelled') NOT NULL DEFAULT 'waiting'");
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->enum('channel', ['store', 'online'])->default('store')->after('status');
        });

        DB::statement("ALTER TABLE pre_order_payments MODIFY payment_method ENUM('cash','debit','qris','transfer','va','card') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        Schema::table('transactions', fn (Blueprint $t) => $t->dropColumn('channel'));
        Schema::table('pre_orders', fn (Blueprint $t) => $t->dropColumn('channel'));
        DB::statement("ALTER TABLE transactions MODIFY payment_method ENUM('cash','debit','qris','transfer') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE pre_orders MODIFY status ENUM('waiting','arrived','completed','cancelled') NOT NULL DEFAULT 'waiting'");
        DB::statement("ALTER TABLE pre_order_payments MODIFY payment_method ENUM('cash','debit','qris','transfer') NOT NULL DEFAULT 'cash'");
    }
};
