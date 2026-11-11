<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/** Status pesanan & lacak pesanan untuk pelanggan. */
class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function show(Request $request, Order $order): View
    {
        self::authorizeOrder($request, $order);
        $this->orders->expireOverdue();

        $order->refresh()->load(['items.product.category', 'items.product.brand', 'payments' => fn ($q) => $q->latest('id')]);

        return view('store.order', [
            'order' => $order,
            'payment' => $order->payments->first(),
            'payUrl' => $order->payments->first() ? CheckoutController::payUrl($order->payments->first(), $request) : null,
        ]);
    }

    public function trackForm(): View
    {
        return view('store.track');
    }

    public function track(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ], [], ['code' => 'kode pesanan']);

        $order = Order::where('code', strtoupper(trim($data['code'])))->first();

        if (! $order || strcasecmp($order->email, $data['email']) !== 0) {
            return back()->withInput()->withErrors(['code' => 'Pesanan tidak ditemukan. Periksa kode dan email.']);
        }

        return redirect()->to(URL::signedRoute('store.order', $order));
    }

    /** Pemilik (member login) atau pemegang link bertanda tangan (tamu). */
    public static function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless(self::ownsOrder($request, $order) || $request->hasValidSignature(), 403, 'Link pesanan tidak valid. Gunakan menu Lacak Pesanan.');
    }

    /** Pesanan milik member yang sedang login (pesanan tamu: customer_id kosong). */
    public static function ownsOrder(Request $request, Order $order): bool
    {
        $customer = $request->user('customer');

        return $customer && $order->customer_id && (int) $order->customer_id === (int) $customer->id;
    }
}
