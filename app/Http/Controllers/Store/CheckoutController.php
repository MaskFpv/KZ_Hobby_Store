<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Checkout ready stock: tamu boleh, member otomatis terisi. */
class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $contents = $this->cart->contents();

        if ($contents['items']->isEmpty()) {
            return redirect()->route('store.cart')->with('store_error', 'Keranjang masih kosong.');
        }

        return view('store.checkout', $contents + ['customer' => $request->user('customer')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'method' => ['required', Rule::in(array_keys(Order::PAYMENT_METHODS))],
            'bank' => ['required_if:method,va', 'nullable', Rule::in(array_keys(OnlinePayment::BANKS))],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan.',
            'bank.required_if' => 'Pilih bank untuk Virtual Account.',
        ], ['name' => 'nama', 'phone' => 'nomor WhatsApp', 'method' => 'metode pembayaran']);

        $contents = $this->cart->contents();
        if ($contents['adjusted']) {
            return redirect()->route('store.cart')->with('store_error', implode(' ', $contents['adjusted']));
        }

        $order = $this->orders->create($contents['items'], $data, $data['method'], $data['bank'] ?? null, $request->user('customer'));
        $this->cart->clear();

        return redirect()->to(self::payUrl($order->payments()->latest('id')->first(), $request));
    }

    /**
     * URL bayar pesanan: member pemilik pakai URL biasa, selain itu signed URL
     * (termasuk member yang membuka pesanan tamunya sendiri lewat link).
     */
    public static function payUrl(OnlinePayment $payment, Request $request): string
    {
        return OrderController::ownsOrder($request, $payment->payable)
            ? route('store.pay', $payment)
            : URL::signedRoute('store.pay', $payment);
    }

    public static function orderUrl(Order $order, Request $request): string
    {
        return OrderController::ownsOrder($request, $order)
            ? route('store.order', $order)
            : URL::signedRoute('store.order', $order);
    }
}
