<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\StockIn;
use App\Services\StockService;
use Carbon\Carbon;

/**
 * Import restock massal (mis. dari invoice supplier).
 * Header: sku*, jumlah*, harga_beli, supplier, status (dipesan/dikirim/diterima), tanggal_pesan, catatan.
 * Status "diterima" langsung menambah stok produk.
 */
class StockInImport extends BaseRowImport
{
    public function __construct(private StockService $stock, private ?int $userId)
    {
    }

    protected function handleRow(array $row): string
    {
        $sku = strtoupper((string) $this->pick($row, ['sku', 'kode_produk'], ''));
        $product = $sku !== '' ? Product::where('sku', $sku)->first() : null;
        if (! $product) {
            throw new \InvalidArgumentException("produk dengan SKU \"{$sku}\" tidak ditemukan.");
        }

        $qty = $this->pick($row, ['jumlah', 'qty', 'quantity']);
        if (! is_numeric($qty) || (int) $qty < 1) {
            throw new \InvalidArgumentException('jumlah harus angka minimal 1.');
        }

        $price = $this->pick($row, ['harga_beli', 'harga_modal', 'purchase_price']);
        $price = $price === null ? (float) $product->purchase_price : $this->money($price);

        $status = $this->status($this->pick($row, ['status'], 'dipesan'));
        $date = $this->date($this->pick($row, ['tanggal_pesan', 'tanggal', 'ordered_at']));

        $stockIn = StockIn::create([
            'product_id' => $product->id,
            'user_id' => $this->userId,
            'supplier' => $this->pick($row, ['supplier', 'pemasok']),
            'quantity' => (int) $qty,
            'purchase_price' => $price,
            'status' => 'ordered',
            'ordered_at' => $date,
            'notes' => $this->pick($row, ['catatan', 'notes']),
        ]);

        if ($status !== 'ordered') {
            $this->stock->changeStatus($stockIn, $status);
        }

        return 'created';
    }

    private function status($value): string
    {
        $v = mb_strtolower(trim((string) $value));

        return match (true) {
            in_array($v, ['dipesan', 'ordered', 'pesan', ''], true) => 'ordered',
            in_array($v, ['dikirim', 'shipped', 'kirim', 'dalam pengiriman'], true) => 'shipped',
            in_array($v, ['diterima', 'received', 'terima'], true) => 'received',
            default => throw new \InvalidArgumentException("status \"{$value}\" tidak dikenal (pakai: dipesan / dikirim / diterima)."),
        };
    }

    private function date($value): string
    {
        if ($value === null || $value === '') {
            return now()->toDateString();
        }

        try {
            // Sel tanggal Excel terbaca sebagai angka serial
            $date = is_numeric($value)
                ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))
                : Carbon::parse($value);
        } catch (\Throwable) {
            throw new \InvalidArgumentException("tanggal_pesan \"{$value}\" tidak valid (format: YYYY-MM-DD).");
        }

        if ($date->isFuture()) {
            throw new \InvalidArgumentException('tanggal_pesan tidak boleh di masa depan.');
        }

        return $date->toDateString();
    }
}
