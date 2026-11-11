<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Kadaluarsakan pesanan & tagihan PO online yang lewat batas bayar (stok dikembalikan).
 * Terjadwal tiap 5 menit di app/Console/Kernel.php (butuh `php artisan schedule:work`
 * atau cron); tanpa scheduler, pengecekan tetap terjadi saat halaman pesanan dibuka.
 */
class ExpireOnlinePayments extends Command
{
    protected $signature = 'store:expire-payments';

    protected $description = 'Kadaluarsakan pesanan/PO online yang belum dibayar sampai batas waktu';

    public function handle(OrderService $orders): int
    {
        $count = $orders->expireOverdue();
        $this->info("{$count} pesanan/PO dikadaluarsakan.");

        return self::SUCCESS;
    }
}
