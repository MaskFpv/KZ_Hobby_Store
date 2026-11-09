<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    use Searchable;
    use HasUniqueSlug;

    public const CONDITIONS = [
        'new' => 'Baru / Mint in Box',
        'second' => 'Second / Back in Box',
    ];

    public const TYPES = [
        'ready_stock' => 'Ready Stock',
        'pre_order' => 'Pre-Order',
    ];

    /** Batas stok menipis untuk dashboard. */
    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'purchase_price',
        'selling_price',
        'stock',
        'condition',
        'type',
        'image',
        'description',
    ];

    protected $searchableFields = ['name', 'sku'];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->sku)) {
                $product->sku = static::generateSku();
            }
        });
    }

    public static function generateSku(): string
    {
        do {
            $sku = 'HB-' . strtoupper(Str::random(8));
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    /* ---------- Relasi ---------- */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function stockIns()
    {
        return $this->hasMany(StockIn::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function preOrders()
    {
        return $this->hasMany(PreOrder::class);
    }

    /* ---------- Scope ---------- */

    public function scopeReadyStock(Builder $query): Builder
    {
        return $query->where('type', 'ready_stock');
    }

    public function scopePreOrder(Builder $query): Builder
    {
        return $query->where('type', 'pre_order');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeLowStock(Builder $query, int $threshold = self::LOW_STOCK_THRESHOLD): Builder
    {
        return $query->where('stock', '<=', $threshold);
    }

    /** Tambah kolom `reserved_qty`: qty yang sudah dialokasikan ke PO berstatus "Barang Tiba". */
    public function scopeWithReserved(Builder $query): Builder
    {
        return $query->withSum(['preOrders as reserved_qty' => fn ($q) => $q->where('status', 'arrived')], 'quantity');
    }

    /** Stok yang boleh dijual di kasir (stok fisik dikurangi jatah PO). */
    public function getAvailableStockAttribute(): int
    {
        // Pakai hasil scopeWithReserved bila ada (null = tidak ada PO), selain itu query langsung
        $reserved = array_key_exists('reserved_qty', $this->attributes)
            ? (int) $this->reserved_qty
            : (int) $this->preOrders()->where('status', 'arrived')->sum('quantity');

        return max(0, $this->stock - (int) $reserved);
    }

    /* ---------- Accessor ---------- */

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** URL gambar (disk public). Pakai asset() agar ikut host yang sedang dibuka. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /** Path absolut gambar untuk DomPDF (tidak bisa ambil via URL). */
    public function getImagePathAttribute(): ?string
    {
        $path = $this->image ? storage_path('app/public/' . $this->image) : null;

        return $path && is_file($path) ? $path : null;
    }

    public function getMarginAttribute(): float
    {
        return (float) $this->selling_price - (float) $this->purchase_price;
    }
}
