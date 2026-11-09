<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'qty',
        'purchase_price',
        'unit_price',
        'sub_total',
    ];

    protected $searchableFields = ['product_name'];

    protected $casts = [
        'qty' => 'integer',
        'purchase_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'sub_total' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
