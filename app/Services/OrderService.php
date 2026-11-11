<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pesanan online ready stock (ambil di toko).
 * Stok dipotong saat pesanan dibuat dan dikembalikan bila kadaluarsa / batal.
 * Transaksi penjualan (omset) dibuat saat barang diserahkan di toko.
 */
class OrderService
{
    public function __construct(private PaymentService $payments)
    {
    }

    /**
     * @param Collection $items  isi CartService::contents()['items'] (product, qty)
     * @param array $contact     name, email, phone, notes
     */
    public function create(Collection $items, array $contact, string $method, ?string $bank, ?Customer $customer): Order
    {
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
        }

        return DB::transaction(function () use ($items, $contact, $method, $bank, $customer) {
            $products = Product::withReserved()->whereIn('id', $items->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');

            $total = 0;
            foreach ($items as $item) {
                $p = $products->get($item->product->id);
                if (! $p || ! $p->is_published || $p->type !== 'ready_stock' || $p->available_stock < $item->qty) {
                    throw ValidationException::withMessages(['cart' => 'Stok ' . ($p->name ?? 'produk') . ' tidak mencukupi. Periksa keranjang lagi.']);
                }
                $total += (float) $p->selling_price * $item->qty;
            }

            $order = Order::create([
                'customer_id' => $customer?->id,
                'name' => $contact['name'],
                'email' => $contact['email'],
                'phone' => $contact['phone'],
                'notes' => $contact['notes'] ?? null,
                'status' => 'pending_payment',
                'total' => $total,
                'payment_method' => $method,
                'payment_due_at' => now()->addHours(config('store.payment_due_hours')),
            ]);

            foreach ($items as $item) {
                $p = $products->get($item->product->id);
                $order->items()->create([
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'qty' => $item->qty,
                    'unit_price' => $p->selling_price,
                    'sub_total' => (float) $p->selling_price * $item->qty,
                ]);
                $p->decrement('stock', $item->qty); // disisihkan untuk pesanan ini
            }

            $this->payments->create($order, $total, $method, 'order', $bank, $order->payment_due_at);

            return $order;
        });
    }

    /** paid -> processing (Gudang/CS mulai menyiapkan barang). */
    public function process(Order $order, User $staff): void
    {
        $this->transition($order, ['paid'], 'processing', ['handled_by' => $staff->id]);
    }

    /** paid/processing -> ready_pickup. */
    public function markReady(Order $order, User $staff): void
    {
        $this->transition($order, ['paid', 'processing'], 'ready_pickup', ['ready_at' => now(), 'handled_by' => $staff->id]);
    }

    /** ready_pickup -> completed: serah terima di toko, transaksi penjualan dicatat. */
    public function handover(Order $order, User $staff): Transaction
    {
        return DB::transaction(function () use ($order, $staff) {
            $order = Order::with('items.product')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($order, ['ready_pickup']);

            $transaction = Transaction::create([
                'id' => Transaction::generateInvoiceNumber(),
                'date' => now()->toDateString(),
                'customer_id' => $order->customer_id,
                'user_id' => $staff->id,
                'subtotal' => $order->total,
                'discount' => 0,
                'total_price' => $order->total,
                'paid_amount' => $order->total,
                'change_amount' => 0,
                'payment_method' => $order->payment_method,
                'channel' => 'online',
                'keterangan' => "Pesanan online {$order->code}",
            ]);

            foreach ($order->items as $item) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'qty' => $item->qty,
                    'purchase_price' => $item->product->purchase_price ?? 0,
                    'unit_price' => $item->unit_price,
                    'sub_total' => $item->sub_total,
                ]);
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'handled_by' => $staff->id,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    /** Batal oleh toko: stok kembali, pembayaran yang sudah masuk ditandai dikembalikan. */
    public function cancel(Order $order, User $staff, ?string $reason): void
    {
        DB::transaction(function () use ($order, $staff, $reason) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($order, ['pending_payment', 'paid', 'processing', 'ready_pickup']);

            $this->restoreStock($order);
            $order->payments()->where('status', 'paid')->update(['status' => 'refunded']);
            $order->payments()->where('status', 'pending')->update(['status' => 'expired']);
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason ?: 'Dibatalkan toko',
                'handled_by' => $staff->id,
            ]);
        });
    }

    /**
     * Kadaluarsakan tagihan yang lewat batas bayar. Dipanggil oleh command terjadwal
     * dan saat halaman pesanan dibuka (php artisan serve tidak menjalankan scheduler).
     *
     * @return int jumlah pesanan + PO yang dikadaluarsakan
     */
    public function expireOverdue(): int
    {
        $count = 0;

        Order::where('status', 'pending_payment')->where('payment_due_at', '<', now())->pluck('id')
            ->each(function ($id) use (&$count) {
                DB::transaction(function () use ($id, &$count) {
                    $order = Order::whereKey($id)->lockForUpdate()->first();
                    if (! $order || $order->status !== 'pending_payment' || $order->payment_due_at->isFuture()) {
                        return;
                    }
                    $this->restoreStock($order);
                    $order->payments()->where('status', 'pending')->update(['status' => 'expired']);
                    $order->update(['status' => 'expired', 'cancel_reason' => 'Tidak dibayar sampai batas waktu']);
                    $count++;
                });
            });

        // Tagihan PO (DP/cicilan) yang lewat batas
        OnlinePayment::where('payable_type', PreOrder::class)->where('status', 'pending')
            ->where('expires_at', '<', now())->get()
            ->each(function (OnlinePayment $payment) use (&$count) {
                DB::transaction(function () use ($payment, &$count) {
                    $po = PreOrder::whereKey($payment->payable_id)->lockForUpdate()->first();
                    // Bersyarat: tagihan yang barusan dibayar tidak boleh tertimpa jadi kadaluarsa
                    if (! OnlinePayment::whereKey($payment->id)->where('status', 'pending')->update(['status' => 'expired'])) {
                        return;
                    }
                    // PO online yang DP-nya tidak pernah dibayar dibatalkan
                    if ($po && $po->status === 'pending_payment' && ! $po->onlinePayments()->where('status', 'pending')->exists()) {
                        $po->update(['status' => 'cancelled', 'notes' => trim(($po->notes ? $po->notes . "\n" : '') . 'DP online tidak dibayar sampai batas waktu.')]);
                        $count++;
                    }
                });
            });

        return $count;
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::whereKey($item->product_id)->increment('stock', $item->qty);
            }
        }
    }

    private function transition(Order $order, array $from, string $to, array $extra = []): void
    {
        DB::transaction(function () use ($order, $from, $to, $extra) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($order, $from);
            $order->update(['status' => $to] + $extra);
        });
    }

    private function assertStatus(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Pesanan {$order->code} berstatus \"{$order->status_label}\", aksi ini tidak bisa dilakukan.",
            ]);
        }
    }
}
