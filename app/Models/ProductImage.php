<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Foto galeri produk (disk public, folder products/gallery). */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
