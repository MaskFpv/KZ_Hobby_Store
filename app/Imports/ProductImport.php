<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

/**
 * Header: sku, nama_produk*, kategori*, brand*, harga_modal*, harga_jual*, stok,
 *         kondisi (baru/second), tipe (ready stock/pre-order), deskripsi.
 *
 * - SKU sudah ada  -> data produk diperbarui, STOK TIDAK diubah
 *                     (stok produk lama hanya berubah lewat Kasir/Stok Masuk/Opname).
 * - SKU baru/kosong -> produk baru dengan stok awal dari kolom `stok`.
 * - Kategori & brand harus sudah terdaftar (dicocokkan tanpa beda huruf besar/kecil).
 */
class ProductImport extends BaseRowImport
{
    private array $categories;
    private array $brands;

    public function __construct()
    {
        $this->categories = Category::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id])->all();
        $this->brands = Brand::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id])->all();
    }

    protected function handleRow(array $row): string
    {
        $name = $this->pick($row, ['nama_produk', 'nama', 'name']);
        if (! $name) {
            throw new \InvalidArgumentException('nama_produk kosong.');
        }

        $categoryName = (string) $this->pick($row, ['kategori', 'category'], '');
        $categoryId = $this->categories[mb_strtolower($categoryName)] ?? null;
        if (! $categoryId) {
            throw new \InvalidArgumentException("kategori \"{$categoryName}\" belum terdaftar.");
        }

        $brandName = (string) $this->pick($row, ['brand', 'pabrikan'], '');
        $brandId = $this->brands[mb_strtolower($brandName)] ?? null;
        if (! $brandId) {
            throw new \InvalidArgumentException("brand \"{$brandName}\" belum terdaftar.");
        }

        $buy = $this->pick($row, ['harga_modal', 'purchase_price']);
        $sell = $this->pick($row, ['harga_jual', 'selling_price']);
        if ($buy === null || $sell === null) {
            throw new \InvalidArgumentException('harga_modal dan harga_jual wajib diisi.');
        }
        $buy = $this->money($buy);
        $sell = $this->money($sell);

        $stock = $this->pick($row, ['stok', 'stock'], 0);
        if (! is_numeric($stock) || (int) $stock < 0) {
            throw new \InvalidArgumentException('stok harus angka >= 0.');
        }

        $sku = strtoupper((string) $this->pick($row, ['sku', 'kode'], ''));
        if ($sku !== '' && ! preg_match('/^[A-Z0-9._\-]{1,64}$/', $sku)) {
            throw new \InvalidArgumentException("SKU \"{$sku}\" tidak valid (tanpa spasi, maks 64 karakter).");
        }

        $data = [
            'name' => mb_substr($name, 0, 255),
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'purchase_price' => $buy,
            'selling_price' => $sell,
            'condition' => $this->condition($this->pick($row, ['kondisi', 'condition'], 'baru')),
            'type' => $this->type($this->pick($row, ['tipe', 'type'], 'ready stock')),
            'description' => $this->pick($row, ['deskripsi', 'description']),
        ];

        $product = $sku !== '' ? Product::where('sku', $sku)->first() : null;

        if ($product) {
            $product->update($data);

            return 'updated';
        }

        Product::create($data + ['sku' => $sku ?: null, 'stock' => (int) $stock]);

        return 'created';
    }

    private function condition($value): string
    {
        $v = mb_strtolower(trim((string) $value));

        return match (true) {
            in_array($v, ['second', 'bekas', 'bib', 'back in box', 'second / back in box'], true) => 'second',
            in_array($v, ['baru', 'new', 'mib', 'mint in box', 'baru / mint in box', ''], true) => 'new',
            default => throw new \InvalidArgumentException("kondisi \"{$value}\" tidak dikenal (pakai: baru / second)."),
        };
    }

    private function type($value): string
    {
        $v = str_replace(['-', '_'], ' ', mb_strtolower(trim((string) $value)));

        return match (true) {
            in_array($v, ['pre order', 'po', 'preorder'], true) => 'pre_order',
            in_array($v, ['ready stock', 'ready', 'rs', ''], true) => 'ready_stock',
            default => throw new \InvalidArgumentException("tipe \"{$value}\" tidak dikenal (pakai: ready stock / pre-order)."),
        };
    }
}
