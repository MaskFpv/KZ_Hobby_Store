<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Item pesanan online. Nama & harga disimpan sebagai snapshot. */
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'product_name', 'qty', 'unit_price', 'sub_total'];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'sub_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
