<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreOrderPayment extends Model
{
    use HasFactory;

    public const TYPES = [
        'dp' => 'Down Payment (DP)',
        'installment' => 'Cicilan',
        'settlement' => 'Pelunasan',
    ];

    protected $fillable = [
        'pre_order_id',
        'user_id',
        'type',
        'amount',
        'payment_method',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function preOrder()
    {
        return $this->belongsTo(PreOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
