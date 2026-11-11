<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDailyCode;
use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreOrder extends Model
{
    use HasFactory;
    use Searchable;
    use GeneratesDailyCode;

    public const CODE_PREFIX = 'PO';

    public const STATUSES = [
        'pending_payment' => 'Menunggu DP',
        'waiting' => 'Menunggu Barang',
        'arrived' => 'Barang Tiba',
        'completed' => 'Selesai / Lunas',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'code',
        'customer_id',
        'product_id',
        'user_id',
        'quantity',
        'unit_price',
        'total_price',
        'paid_amount',
        'order_date',
        'estimated_arrival',
        'status',
        'channel',
        'transaction_id',
        'notes',
    ];

    protected $searchableFields = ['code', 'notes'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'order_date' => 'date',
        'estimated_arrival' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(PreOrderPayment::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Tagihan pembayaran online (DP/cicilan/pelunasan dari website). */
    public function onlinePayments()
    {
        return $this->morphMany(OnlinePayment::class, 'payable');
    }

    /** Hitung ulang paid_amount dari tabel pembayaran. */
    public function refreshPaidAmount(): void
    {
        $this->update(['paid_amount' => $this->payments()->sum('amount')]);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_price - (float) $this->paid_amount);
    }

    public function getIsPaidOffAttribute(): bool
    {
        return $this->remaining_amount <= 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Kelas badge Bootstrap per status. */
    public function getStatusBadgeAttribute(): string
    {
        return [
            'pending_payment' => 'bg-light text-dark border',
            'waiting' => 'bg-warning text-dark',
            'arrived' => 'bg-info text-dark',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
        ][$this->status] ?? 'bg-light text-dark';
    }

    public function isOpen(): bool
    {
        // Belum selesai/batal. Antrean stok tetap hanya untuk status 'waiting' (lihat StockService).
        return in_array($this->status, ['pending_payment', 'waiting', 'arrived'], true);
    }
}
