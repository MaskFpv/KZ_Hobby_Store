<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    use Searchable;

    public const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'debit' => 'Debit',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
    ];

    /** ID = nomor faktur YYYYMMDD0001, diisi manual. */
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'date',
        'customer_id',
        'user_id',
        'subtotal',
        'discount',
        'total_price',
        'paid_amount',
        'change_amount',
        'payment_method',
        'keterangan',
    ];

    protected $searchableFields = ['id', 'keterangan'];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    /**
     * Nomor faktur berikutnya untuk hari ini (YYYYMMDD + 4 digit urut).
     * Panggil di dalam DB::transaction agar lockForUpdate efektif.
     */
    public static function generateInvoiceNumber(): int
    {
        $base = (int) now()->format('Ymd') * 10000;

        $last = static::whereBetween('id', [$base + 1, $base + 9999])
            ->lockForUpdate()
            ->max('id');

        return $last ? $last + 1 : $base + 1;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function preOrder()
    {
        return $this->hasOne(PreOrder::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
