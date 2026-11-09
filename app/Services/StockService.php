<?php

namespace App\Services;

use App\Models\PreOrder;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Semua perubahan stok di luar kasir lewat sini, supaya selalu tercatat
 * dan aman dari race condition (lockForUpdate di dalam DB transaction).
 */
class StockService
{
    /** Status lanjutan yang boleh dari tiap status stok masuk. */
    public const TRANSITIONS = [
        'ordered' => ['shipped', 'received', 'cancelled'],
        'shipped' => ['received', 'cancelled'],
        'received' => [],
        'cancelled' => [],
    ];

    /**
     * Ubah status stok masuk. Status `received` menambah stok produk dan
     * otomatis menandai PO yang menunggu barang ini sebagai "Barang Tiba".
     *
     * @return int jumlah PO yang ditandai tiba
     */
    public function changeStatus(StockIn $stockIn, string $status, bool $updateCost = false): int
    {
        return DB::transaction(function () use ($stockIn, $status, $updateCost) {
            $stockIn = StockIn::whereKey($stockIn->id)->lockForUpdate()->firstOrFail();

            if (! in_array($status, self::TRANSITIONS[$stockIn->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Status tidak bisa diubah dari \"{$stockIn->status_label}\" ke \"" . (StockIn::STATUSES[$status] ?? $status) . '".',
                ]);
            }

            $stockIn->status = $status;

            if ($status !== 'received') {
                $stockIn->save();

                return 0;
            }

            $stockIn->received_at = now();
            $stockIn->save();

            $product = Product::whereKey($stockIn->product_id)->lockForUpdate()->firstOrFail();
            $product->stock += $stockIn->quantity;
            if ($updateCost && $stockIn->purchase_price > 0) {
                $product->purchase_price = $stockIn->purchase_price;
            }
            $product->save();

            return $this->allocateWaitingPreOrders($product);
        });
    }

    /**
     * Tandai PO "menunggu barang" sebagai "barang tiba" (urut pesanan paling lama)
     * selama stok bebas mencukupi. Stok bebas = stok - qty PO yang sudah tiba.
     */
    public function allocateWaitingPreOrders(Product $product): int
    {
        $available = $product->stock - $this->reservedForPreOrders($product->id);
        $count = 0;

        $waiting = PreOrder::where('product_id', $product->id)
            ->where('status', 'waiting')
            ->orderBy('order_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($waiting as $po) {
            if ($po->quantity > $available) {
                break; // FIFO: jangan lompati antrean
            }
            $po->update(['status' => 'arrived']);
            $available -= $po->quantity;
            $count++;
        }

        return $count;
    }

    /** Qty yang sudah dialokasikan untuk PO berstatus "Barang Tiba". */
    public function reservedForPreOrders(int $productId, ?int $exceptPreOrderId = null): int
    {
        return (int) PreOrder::where('product_id', $productId)
            ->where('status', 'arrived')
            ->when($exceptPreOrderId, fn ($q) => $q->where('id', '!=', $exceptPreOrderId))
            ->sum('quantity');
    }

    /**
     * Simpan hasil opname untuk banyak produk sekaligus.
     *
     * @param array<int, array{physical:int, notes:?string}> $counts key = product_id
     * @return int jumlah produk yang stoknya berubah
     */
    public function opname(array $counts, string $date, ?int $userId, ?string $generalNote = null): int
    {
        return DB::transaction(function () use ($counts, $date, $userId, $generalNote) {
            $products = Product::whereIn('id', array_keys($counts))->lockForUpdate()->get()->keyBy('id');
            $changed = 0;

            foreach ($counts as $productId => $row) {
                $product = $products->get($productId);
                if (! $product) {
                    continue;
                }

                $physical = (int) $row['physical'];
                $difference = $physical - $product->stock;
                $note = trim(implode(' | ', array_filter([$row['notes'] ?? null, $generalNote])));

                StockOpname::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'opname_date' => $date,
                    'system_stock' => $product->stock,
                    'physical_stock' => $physical,
                    'difference' => $difference,
                    'notes' => $note ?: null,
                ]);

                if ($difference !== 0) {
                    $product->update(['stock' => $physical]);
                    $changed++;
                }

                // Stok bertambah: PO yang menunggu barang ini bisa langsung dialokasikan
                if ($difference > 0) {
                    $this->allocateWaitingPreOrders($product);
                }
            }

            return $changed;
        });
    }
}
