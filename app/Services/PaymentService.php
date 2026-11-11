<?php

namespace App\Services;

use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PreOrder;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gateway pembayaran PROTOTYPE (QRIS, Virtual Account, Kartu).
 * markPaid() adalah titik yang nanti diganti callback/webhook gateway sungguhan.
 */
class PaymentService
{
    public function __construct(private StockService $stock)
    {
    }

    /** Buat tagihan baru untuk Order / PreOrder. */
    public function create(Model $payable, float $amount, string $method, string $purpose, ?string $bank = null, $expiresAt = null): OnlinePayment
    {
        if ($method === 'va' && ! isset(OnlinePayment::BANKS[$bank])) {
            throw ValidationException::withMessages(['bank' => 'Pilih bank untuk Virtual Account.']);
        }

        return $payable->morphMany(OnlinePayment::class, 'payable')->create([
            'purpose' => $purpose,
            'method' => $method,
            'bank' => $method === 'va' ? $bank : null,
            'va_number' => $method === 'va' ? $this->vaNumber($bank) : null,
            'amount' => $amount,
            'status' => 'pending',
            'expires_at' => $expiresAt ?? now()->addHours(config('store.payment_due_hours')),
        ]);
    }

    /**
     * Validasi data kartu (prototype). Return [merek, 4 digit terakhir].
     * Nomor penuh & CVV TIDAK disimpan ke mana pun.
     */
    public function cardDetails(?string $number, ?string $expiry, ?string $cvc): array
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        if (strlen($digits) < 13 || strlen($digits) > 19 || ! $this->luhn($digits)) {
            throw ValidationException::withMessages(['card_number' => 'Nomor kartu tidak valid.']);
        }
        if (! preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', (string) $expiry, $m)
            || now()->startOfMonth()->gt(now()->setDate(2000 + (int) $m[2], (int) $m[1], 1)->startOfMonth())) {
            throw ValidationException::withMessages(['card_expiry' => 'Masa berlaku kartu tidak valid atau sudah lewat.']);
        }
        if (! preg_match('/^\d{3,4}$/', (string) $cvc)) {
            throw ValidationException::withMessages(['card_cvc' => 'CVV harus 3–4 digit.']);
        }

        $brand = match (true) {
            str_starts_with($digits, '4') => 'visa',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $digits) => 'mastercard',
            (bool) preg_match('/^35/', $digits) => 'jcb',
            (bool) preg_match('/^3[47]/', $digits) => 'amex',
            default => 'card',
        };

        return [$brand, substr($digits, -4)];
    }

    /** Tandai lunas & teruskan efeknya ke pesanan / PO. Aman dipanggil dua kali. */
    public function markPaid(OnlinePayment $payment, ?array $card = null): OnlinePayment
    {
        return DB::transaction(function () use ($payment, $card) {
            // Kunci pesanan/PO dulu baru tagihannya: urutan sama dengan batal & kadaluarsa (cegah deadlock)
            $payable = $payment->payable()->lockForUpdate()->firstOrFail();
            $payment = OnlinePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'paid') {
                return $payment;
            }
            if (! $payment->isPending()) {
                throw ValidationException::withMessages(['payment' => 'Tagihan ini sudah kadaluarsa. Buat pembayaran baru.']);
            }

            if ($payable instanceof Order) {
                if ($payable->status !== 'pending_payment') {
                    throw ValidationException::withMessages(['payment' => "Pesanan {$payable->code} tidak menunggu pembayaran."]);
                }
                $payable->update(['status' => 'paid', 'paid_at' => now()]);
            }

            if ($payable instanceof PreOrder) {
                $this->applyToPreOrder($payable, $payment);
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'card_brand' => $card[0] ?? null,
                'card_last4' => $card[1] ?? null,
            ]);

            return $payment;
        });
    }

    /** Batalkan tagihan lain yang masih menunggu untuk objek yang sama (mis. ganti nominal cicilan). */
    public function expirePending(Model $payable): void
    {
        $payable->morphMany(OnlinePayment::class, 'payable')->where('status', 'pending')->update(['status' => 'expired']);
    }

    private function applyToPreOrder(PreOrder $po, OnlinePayment $payment): void
    {
        if (in_array($po->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['payment' => "PO {$po->code} sudah {$po->status_label}."]);
        }
        if ((float) $payment->amount > $po->remaining_amount + 0.001) {
            throw ValidationException::withMessages(['payment' => 'Nominal melebihi sisa tagihan PO. Buat pembayaran baru.']);
        }

        $po->payments()->create([
            'type' => $payment->purpose === 'order' ? 'installment' : $payment->purpose,
            'amount' => $payment->amount,
            'payment_method' => $payment->method,
            'paid_at' => now(),
            'notes' => "Online {$payment->code}",
        ]);
        $po->refreshPaidAmount();

        // DP pertama masuk: PO resmi masuk antrean barang
        if ($po->status === 'pending_payment') {
            $po->update(['status' => 'waiting']);
            $this->stock->allocateWaitingPreOrders(Product::findOrFail($po->product_id));
        }
    }

    private function vaNumber(string $bank): string
    {
        do {
            $number = OnlinePayment::BANKS[$bank][1] . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        } while (OnlinePayment::where('va_number', $number)->exists());

        return $number;
    }

    private function luhn(string $digits): bool
    {
        $sum = 0;
        foreach (array_reverse(str_split($digits)) as $i => $d) {
            $d = (int) $d;
            if ($i % 2 === 1) {
                $d *= 2;
                $d = $d > 9 ? $d - 9 : $d;
            }
            $sum += $d;
        }

        return $sum % 10 === 0;
    }
}
