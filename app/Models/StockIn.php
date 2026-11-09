<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDailyCode;
use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;
    use Searchable;
    use GeneratesDailyCode;

    public const CODE_PREFIX = 'SI';

    public const STATUSES = [
        'ordered' => 'Dipesan ke Supplier',
        'shipped' => 'Dalam Pengiriman',
        'received' => 'Diterima',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'code',
        'product_id',
        'user_id',
        'supplier',
        'quantity',
        'purchase_price',
        'status',
        'ordered_at',
        'received_at',
        'notes',
    ];

    protected $searchableFields = ['code', 'supplier'];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'ordered_at' => 'date',
        'received_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Kelas badge Bootstrap per status. */
    public function getStatusBadgeAttribute(): string
    {
        return [
            'ordered' => 'bg-secondary',
            'shipped' => 'bg-info text-dark',
            'received' => 'bg-success',
            'cancelled' => 'bg-danger',
        ][$this->status] ?? 'bg-light text-dark';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }
}
