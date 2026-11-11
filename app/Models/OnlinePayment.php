<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDailyCode;
use Illuminate\Database\Eloquent\Model;

/**
 * Tagihan pembayaran online (prototype). payable = Order atau PreOrder.
 * Kartu: hanya merek + 4 digit terakhir yang disimpan.
 */
class OnlinePayment extends Model
{
    use GeneratesDailyCode;

    public const CODE_PREFIX = 'PAY';

    public const METHODS = Order::PAYMENT_METHODS;

    /** Kode bank => [nama, prefix nomor VA]. */
    public const BANKS = [
        'bca' => ['BCA', '39358'],
        'bni' => ['BNI', '8808'],
        'bri' => ['BRI', '26215'],
        'mandiri' => ['Mandiri', '89508'],
    ];

    public const PURPOSES = [
        'order' => 'Pesanan',
        'dp' => 'DP Pre-Order',
        'installment' => 'Cicilan Pre-Order',
        'settlement' => 'Pelunasan Pre-Order',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu',
        'paid' => 'Lunas',
        'expired' => 'Kadaluarsa',
        'refunded' => 'Dikembalikan',
    ];

    protected $fillable = [
        'code', 'payable_type', 'payable_id', 'purpose', 'method', 'bank', 'va_number',
        'card_brand', 'card_last4', 'amount', 'status', 'expires_at', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    public function getMethodLabelAttribute(): string
    {
        $label = self::METHODS[$this->method] ?? $this->method;

        return match ($this->method) {
            'va' => $label . ' ' . (self::BANKS[$this->bank][0] ?? ''),
            'card' => $this->card_last4 ? strtoupper((string) $this->card_brand) . ' •••• ' . $this->card_last4 : $label,
            default => $label,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPurposeLabelAttribute(): string
    {
        return self::PURPOSES[$this->purpose] ?? $this->purpose;
    }

    /** Isi QR prototipe (bukan QRIS sungguhan). */
    public function getQrisPayloadAttribute(): string
    {
        return 'KZHOBBY|QRIS-DEMO|' . $this->code . '|' . (int) $this->amount;
    }
}
