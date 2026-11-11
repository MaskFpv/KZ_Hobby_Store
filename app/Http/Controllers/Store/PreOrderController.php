<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PreOrder;
use App\Services\OrderService;
use App\Services\PreOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pre-order online oleh member (wajib login guard customer). */
class PreOrderController extends Controller
{
    public const MAX_QTY = 5;

    public function __construct(private PreOrderService $preOrders, private OrderService $orders)
    {
    }

    public function create(string $slug): View
    {
        $product = StoreController::visible()->preOrder()->where('slug', $slug)->firstOrFail();

        return view('store.po-create', ['product' => $product, 'maxQty' => self::MAX_QTY]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $product = StoreController::visible()->preOrder()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:' . self::MAX_QTY],
            'dp_amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(array_keys(Order::PAYMENT_METHODS))],
            'bank' => ['required_if:method,va', 'nullable', Rule::in(array_keys(OnlinePayment::BANKS))],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui ketentuan DP pre-order.',
            'bank.required_if' => 'Pilih bank untuk Virtual Account.',
        ], ['qty' => 'jumlah', 'dp_amount' => 'DP', 'method' => 'metode pembayaran']);

        $payment = $this->preOrders->createOnline(
            $request->user('customer'), $product, (int) $data['qty'], (float) $data['dp_amount'], $data['method'], $data['bank'] ?? null
        );

        return redirect()->route('store.pay', $payment);
    }

    public function show(Request $request, string $code): View
    {
        $this->orders->expireOverdue();

        $po = PreOrder::with(['product.category', 'product.brand', 'payments', 'onlinePayments' => fn ($q) => $q->latest('id')])
            ->where('code', $code)->where('customer_id', $request->user('customer')->id)->firstOrFail();

        return view('store.po-show', [
            'po' => $po,
            'pendingPayment' => $po->onlinePayments->first(fn ($p) => $p->isPending()),
        ]);
    }

    /** Bayar cicilan / pelunasan dari akun. */
    public function pay(Request $request, string $code): RedirectResponse
    {
        $po = PreOrder::where('code', $code)->where('customer_id', $request->user('customer')->id)->firstOrFail();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(array_keys(Order::PAYMENT_METHODS))],
            'bank' => ['required_if:method,va', 'nullable', Rule::in(array_keys(OnlinePayment::BANKS))],
        ], ['bank.required_if' => 'Pilih bank untuk Virtual Account.'], ['amount' => 'nominal', 'method' => 'metode pembayaran']);

        $payment = $this->preOrders->requestOnlinePayment($po, (float) $data['amount'], $data['method'], $data['bank'] ?? null);

        return redirect()->route('store.pay', $payment);
    }
}
