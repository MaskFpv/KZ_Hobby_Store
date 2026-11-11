<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDailyCode;
use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Pesanan online (ready stock, ambil di toko). */
class Order extends Model
{
    use HasFactory;
    use Searchable;
    use GeneratesDailyCode;

    public const CODE_PREFIX = 'ORD';

    public const STATUSES = [
        'pending_payment' => 'Menunggu Pembayaran',
        'paid' => 'Dibayar',
        'processing' => 'Disiapkan',
        'ready_pickup' => 'Siap Diambil',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'expired' => 'Kadaluarsa',
    ];

    /** Urutan langkah untuk timeline status. */
    public const FLOW = ['pending_payment', 'paid', 'processing', 'ready_pickup', 'completed'];

    public const PAYMENT_METHODS = [
        'qris' => 'QRIS',
        'va' => 'Virtual Account',
        'card' => 'Kartu Kredit/Debit',
    ];

    protected $fillable = [
        'code', 'customer_id', 'name', 'email', 'phone', 'status', 'total', 'payment_method',
        'payment_due_at', 'paid_at', 'ready_at', 'completed_at', 'cancelled_at', 'cancel_reason',
        'handled_by', 'transaction_id', 'notes',
    ];

    protected $searchableFields = ['code', 'name', 'email', 'phone'];

    protected $casts = [
        'total' => 'decimal:2',
        'payment_due_at' => 'datetime',
        'paid_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->morphMany(OnlinePayment::class, 'payable');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return [
            'pending_payment' => 'bg-warning text-dark',
            'paid' => 'bg-info text-dark',
            'processing' => 'bg-primary',
            'ready_pickup' => 'bg-success',
            'completed' => 'bg-secondary',
            'cancelled' => 'bg-danger',
            'expired' => 'bg-dark',
        ][$this->status] ?? 'bg-light text-dark';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /** Masih berjalan (belum selesai / batal / kadaluarsa). */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending_payment', 'paid', 'processing', 'ready_pickup'], true);
    }

    public function wasPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
