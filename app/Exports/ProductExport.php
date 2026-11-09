<?php

namespace App\Exports;

use App\Http\Controllers\ProductController;
use Illuminate\Support\Collection;

class ProductExport extends BaseSheetExport
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Katalog Produk';
    }

    public function headings(): array
    {
        return ['No', 'SKU', 'Nama Produk', 'Kategori', 'Brand', 'Kondisi', 'Tipe', 'Harga Modal', 'Harga Jual', 'Stok', 'Nilai Stok (Modal)'];
    }

    protected function currencyColumns(): array
    {
        return ['H', 'I', 'K'];
    }

    protected function rows(): Collection
    {
        return ProductController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($p, $i) => [
                $i + 1,
                $p->sku,
                $p->name,
                $p->category->name,
                $p->brand->name,
                $p->condition_label,
                $p->type_label,
                (float) $p->purchase_price,
                (float) $p->selling_price,
                $p->stock,
                (float) $p->purchase_price * $p->stock,
            ]);
    }
}
