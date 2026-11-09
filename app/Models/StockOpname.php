<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'product_id',
        'user_id',
        'opname_date',
        'system_stock',
        'physical_stock',
        'difference',
        'notes',
    ];

    protected $searchableFields = ['notes'];

    protected $casts = [
        'opname_date' => 'date',
        'system_stock' => 'integer',
        'physical_stock' => 'integer',
        'difference' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
