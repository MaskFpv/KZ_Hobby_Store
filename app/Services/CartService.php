<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Keranjang belanja website, disimpan di session: [product_id => qty].
 * Hanya produk Ready Stock yang tampil di website. Stok dicek ulang saat checkout.
 */
class CartService
{
    private const KEY = 'cart';

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function add(int $productId, int $qty): void
    {
        $product = $this->sellable($productId);
        $cart = $this->raw();
        $this->put($product, ($cart[$productId] ?? 0) + $qty);
    }

    public function update(int $productId, int $qty): void
    {
        $qty < 1 ? $this->remove($productId) : $this->put($this->sellable($productId), $qty);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Isi keranjang dengan data produk terbaru. Produk yang sudah tidak dijual dibuang,
     * qty disesuaikan bila stok berkurang (dicatat di $adjusted).
     *
     * @return array{items: Collection, total: float, adjusted: array}
     */
    public function contents(): array
    {
        $cart = $this->raw();
        $products = Product::with(['brand:id,name', 'category:id,name,slug'])->withReserved()
            ->whereIn('id', array_keys($cart))->get()->keyBy('id');

        $items = collect();
        $adjusted = [];

        foreach ($cart as $id => $qty) {
            $p = $products->get($id);
            if (! $p || ! $p->is_published || $p->type !== 'ready_stock' || $p->available_stock < 1) {
                $adjusted[] = ($p->name ?? 'Produk') . ' sudah tidak tersedia dan dihapus dari keranjang.';
                unset($cart[$id]);
                continue;
            }
            if ($qty > $p->available_stock) {
                $adjusted[] = "Jumlah {$p->name} disesuaikan dengan stok ({$p->available_stock}).";
                $qty = $cart[$id] = $p->available_stock;
            }
            $items->push((object) ['product' => $p, 'qty' => $qty, 'subtotal' => (float) $p->selling_price * $qty]);
        }

        session([self::KEY => $cart]);

        return ['items' => $items, 'total' => (float) $items->sum('subtotal'), 'adjusted' => $adjusted];
    }

    private function sellable(int $productId): Product
    {
        $product = Product::withReserved()->whereKey($productId)->where('is_published', true)->first();

        if (! $product) {
            throw ValidationException::withMessages(['product_id' => 'Produk tidak ditemukan.']);
        }
        if ($product->type !== 'ready_stock') {
            throw ValidationException::withMessages(['product_id' => 'Produk pre-order dipesan lewat tombol Pre-Order, bukan keranjang.']);
        }

        return $product;
    }

    private function put(Product $product, int $qty): void
    {
        if ($product->available_stock < 1) {
            throw ValidationException::withMessages(['qty' => "Stok {$product->name} habis."]);
        }
        if ($qty > $product->available_stock) {
            throw ValidationException::withMessages(['qty' => "Stok {$product->name} tersisa {$product->available_stock}."]);
        }

        $cart = $this->raw();
        $cart[$product->id] = $qty;
        session([self::KEY => $cart]);
    }
}
