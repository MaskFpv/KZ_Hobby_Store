<?php

namespace App\Services;

use App\Models\PreOrder;
use App\Models\PreOrderPayment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur Pre-Order:
 *   [online] pending_payment (menunggu DP) -> waiting saat DP dibayar
 *   waiting (menunggu barang) -> arrived (barang tiba, stok dialokasikan)
 *   -> completed (lunas & diambil: jadi transaksi penjualan, stok dipotong)
 *   waiting/arrived -> cancelled
 *
 * Omset PO dicatat saat pelunasan (transaksi dibuat sebesar total PO).
 */
class PreOrderService
{
    public function __construct(private StockService $stock, private PaymentService $payments)
    {
    }

    /** PO dari website: dibuat menunggu DP, plus tagihan DP online. */
    public function createOnline(\App\Models\Customer $customer, Product $product, int $qty, float $dp, string $method, ?string $bank): \App\Models\OnlinePayment
    {
        if (! $product->is_published || ! $product->isPreOrderOpen()) {
            throw ValidationException::withMessages(['product' => 'Pre-order produk ini sudah ditutup.']);
        }

        $total = round((float) $product->selling_price * $qty, 2);
        $min = (float) ceil($total * config('store.dp_min_percent') / 100);

        if ($dp < $min || $dp > $total) {
            throw ValidationException::withMessages([
                'dp_amount' => 'DP minimal Rp ' . number_format($min, 0, ',', '.') . ' dan maksimal Rp ' . number_format($total, 0, ',', '.') . '.',
            ]);
        }

        return DB::transaction(function () use ($customer, $product, $qty, $total, $dp, $method, $bank) {
            $po = PreOrder::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $product->selling_price,
                'total_price' => $total,
                'paid_amount' => 0,
                'order_date' => now()->toDateString(),
                'status' => 'pending_payment',
                'channel' => 'online',
            ]);

            return $this->payments->create($po, $dp, $method, 'dp', $bank);
        });
    }

    /** Member membayar cicilan / pelunasan PO dari website. */
    public function requestOnlinePayment(PreOrder $po, float $amount, string $method, ?string $bank): \App\Models\OnlinePayment
    {
        if (! $po->isOpen()) {
            throw ValidationException::withMessages(['amount' => "PO {$po->code} tidak sedang menunggu pembayaran."]);
        }
        if ($amount < 1 || $amount > $po->remaining_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal harus antara Rp 1 dan sisa tagihan Rp ' . number_format($po->remaining_amount, 0, ',', '.') . '.',
            ]);
        }

        return DB::transaction(function () use ($po, $amount, $method, $bank) {
            // Satu tagihan aktif per PO: tagihan lama yang belum dibayar diganti
            $this->payments->expirePending($po);

            $purpose = abs($amount - $po->remaining_amount) < 0.01 ? 'settlement' : 'installment';

            return $this->payments->create($po, $amount, $method, $purpose, $bank);
        });
    }

    public function create(array $data, float $dp, string $paymentMethod, ?int $userId): PreOrder
    {
        return DB::transaction(function () use ($data, $dp, $paymentMethod, $userId) {
            $product = Product::findOrFail($data['product_id']);
            $total = round((float) $data['unit_price'] * (int) $data['quantity'], 2);

            if ($dp > $total) {
                throw ValidationException::withMessages(['dp_amount' => 'DP tidak boleh melebihi total PO.']);
            }

            $po = PreOrder::create($data + [
                'user_id' => $userId,
                'total_price' => $total,
                'paid_amount' => 0,
                'status' => 'waiting',
            ]);

            if ($dp > 0) {
                $po->payments()->create([
                    'user_id' => $userId,
                    'type' => 'dp',
                    'amount' => $dp,
                    'payment_method' => $paymentMethod,
                    'paid_at' => now(),
                ]);
                $po->refreshPaidAmount();
            }

            // Barang mungkin sudah ada di stok (restock datang lebih dulu)
            $this->stock->allocateWaitingPreOrders($product);

            return $po->fresh();
        });
    }

    public function addPayment(PreOrder $po, float $amount, string $method, ?string $notes, ?int $userId): PreOrderPayment
    {
        return DB::transaction(function () use ($po, $amount, $method, $notes, $userId) {
            $po = PreOrder::whereKey($po->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($po);

            if ($amount <= 0 || $amount > $po->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal harus lebih dari 0 dan maksimal sisa tagihan Rp ' . number_format($po->remaining_amount, 0, ',', '.') . '.',
                ]);
            }

            $payment = $po->payments()->create([
                'user_id' => $userId,
                'type' => $po->payments()->exists() ? 'installment' : 'dp',
                'amount' => $amount,
                'payment_method' => $method,
                'paid_at' => now(),
                'notes' => $notes,
            ]);
            $po->refreshPaidAmount();

            // PO online yang DP-nya dibayar langsung di toko
            if ($po->status === 'pending_payment') {
                $this->payments->expirePending($po);
                $po->update(['status' => 'waiting']);
                $this->stock->allocateWaitingPreOrders(Product::findOrFail($po->product_id));
            }

            return $payment;
        });
    }

    /** Tandai barang tiba secara manual (stok bebas harus cukup). */
    public function markArrived(PreOrder $po): void
    {
        DB::transaction(function () use ($po) {
            $po = PreOrder::whereKey($po->id)->lockForUpdate()->firstOrFail();

            if ($po->status !== 'waiting') {
                throw ValidationException::withMessages(['status' => 'Hanya PO berstatus "Menunggu Barang" yang bisa ditandai tiba.']);
            }

            $product = Product::whereKey($po->product_id)->lockForUpdate()->firstOrFail();
            $free = $product->stock - $this->stock->reservedForPreOrders($product->id);

            if ($free < $po->quantity) {
                throw ValidationException::withMessages([
                    'status' => "Stok bebas {$product->name} hanya {$free}. Catat dulu barang masuk lewat menu Stok Masuk.",
                ]);
            }

            $po->update(['status' => 'arrived']);
        });
    }

    /**
     * Pelunasan & serah terima: bayar sisa (jika ada), potong stok,
     * buat transaksi penjualan, PO selesai.
     */
    public function complete(PreOrder $po, string $method, ?int $userId): Transaction
    {
        return DB::transaction(function () use ($po, $method, $userId) {
            $po = PreOrder::whereKey($po->id)->lockForUpdate()->firstOrFail();

            if ($po->status !== 'arrived') {
                throw ValidationException::withMessages(['status' => 'PO hanya bisa dilunasi setelah barang tiba.']);
            }

            $product = Product::whereKey($po->product_id)->lockForUpdate()->firstOrFail();
            if ($product->stock < $po->quantity) {
                throw ValidationException::withMessages(['status' => "Stok {$product->name} tidak mencukupi ({$product->stock})."]);
            }

            $remaining = $po->remaining_amount;
            if ($remaining > 0) {
                $po->payments()->create([
                    'user_id' => $userId,
                    'type' => 'settlement',
                    'amount' => $remaining,
                    'payment_method' => $method,
                    'paid_at' => now(),
                ]);
                $po->refreshPaidAmount();
            }

            $transaction = Transaction::create([
                'id' => Transaction::generateInvoiceNumber(),
                'date' => now()->toDateString(),
                'customer_id' => $po->customer_id,
                'user_id' => $userId,
                'subtotal' => $po->total_price,
                'discount' => 0,
                'total_price' => $po->total_price,
                'paid_amount' => $po->total_price,
                'change_amount' => 0,
                'payment_method' => $method,
                'keterangan' => "Pelunasan Pre-Order {$po->code}",
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'qty' => $po->quantity,
                'purchase_price' => $product->purchase_price,
                'unit_price' => $po->unit_price,
                'sub_total' => $po->total_price,
            ]);

            $product->decrement('stock', $po->quantity);

            $po->update(['status' => 'completed', 'transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    public function cancel(PreOrder $po, ?string $reason): void
    {
        DB::transaction(function () use ($po, $reason) {
            $po = PreOrder::whereKey($po->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($po);

            $notes = trim(($po->notes ? $po->notes . "\n" : '') . 'Dibatalkan ' . now()->format('d-m-Y') . ($reason ? ": {$reason}" : ''));
            $po->update(['status' => 'cancelled', 'notes' => $notes]);
            $this->payments->expirePending($po); // tagihan online yang belum dibayar ikut batal

            // Stok yang dialokasikan ke PO ini bisa dipakai antrean PO berikutnya
            $this->stock->allocateWaitingPreOrders(Product::findOrFail($po->product_id));
        });
    }

    private function ensureOpen(PreOrder $po): void
    {
        if (in_array($po->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => "PO {$po->code} sudah {$po->status_label}."]);
        }
    }
}
