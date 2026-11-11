<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PreOrder;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Layar pembayaran prototype (QRIS / VA / Kartu). */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments, private OrderService $orders)
    {
    }

    public function show(Request $request, OnlinePayment $payment): View
    {
        $this->authorizePayment($request, $payment);
        $this->orders->expireOverdue();
        $payment->refresh()->load('payable');

        return view('store.pay', [
            'payment' => $payment,
            'payable' => $payment->payable,
            'backUrl' => $this->backUrl($payment, $request),
        ]);
    }

    /** Tombol "simulasikan bayar" (QRIS/VA) atau submit form kartu. */
    public function confirm(Request $request, OnlinePayment $payment): RedirectResponse
    {
        $this->authorizePayment($request, $payment);

        $card = null;
        if ($payment->method === 'card') {
            $request->validate([
                'card_name' => ['required', 'string', 'max:100'],
                'card_number' => ['required', 'string'],
                'card_expiry' => ['required', 'string'],
                'card_cvc' => ['required', 'string'],
            ], [], ['card_name' => 'nama di kartu', 'card_number' => 'nomor kartu', 'card_expiry' => 'masa berlaku', 'card_cvc' => 'CVV']);

            $card = $this->payments->cardDetails($request->card_number, $request->card_expiry, $request->card_cvc);
        }

        try {
            $this->payments->markPaid($payment, $card);
        } catch (ValidationException $e) {
            return redirect()->to($this->backUrl($payment, $request))->with('store_error', collect($e->errors())->flatten()->first());
        }

        return redirect()->to($this->backUrl($payment, $request))
            ->with('store_success', 'Pembayaran Rp ' . number_format($payment->amount, 0, ',', '.') . ' berhasil. Arigatou!');
    }

    private function authorizePayment(Request $request, OnlinePayment $payment): void
    {
        $payable = $payment->payable;

        if ($payable instanceof Order) {
            OrderController::authorizeOrder($request, $payable);

            return;
        }

        // PO online: hanya member pemilik
        $customer = $request->user('customer');
        abort_unless($payable instanceof PreOrder && $customer && $payable->customer_id === $customer->id, 403);
    }

    private function backUrl(OnlinePayment $payment, Request $request): string
    {
        return $payment->payable instanceof Order
            ? CheckoutController::orderUrl($payment->payable, $request)
            : route('store.po.show', $payment->payable->code);
    }
}
