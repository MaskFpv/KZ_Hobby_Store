<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function index(): View
    {
        return view('store.cart', $this->cart->contents());
    }

    /** Tambah ke keranjang. Mendukung fetch (JSON) untuk animasi tanpa reload. */
    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $this->cart->add((int) $data['product_id'], (int) ($data['qty'] ?? 1));

        if ($request->expectsJson()) {
            return response()->json(['count' => $this->cart->count(), 'message' => 'Ditambahkan ke keranjang.']);
        }

        return redirect()->route('store.cart')->with('store_success', 'Ditambahkan ke keranjang.');
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate(['qty' => ['required', 'integer', 'min:0', 'max:99']]);
        $this->cart->update($product, (int) $data['qty']);

        return redirect()->route('store.cart');
    }

    public function remove(int $product): RedirectResponse
    {
        $this->cart->remove($product);

        return redirect()->route('store.cart')->with('store_success', 'Produk dihapus dari keranjang.');
    }
}
