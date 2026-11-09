<?php

namespace App\Exports;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryExport extends BaseSheetExport
{
    protected function title(): string
    {
        return 'Data Kategori Produk';
    }

    public function headings(): array
    {
        return ['No', 'Nama Kategori', 'Deskripsi', 'Jumlah Produk'];
    }

    protected function rows(): Collection
    {
        return Category::withCount('products')->orderBy('name')->get()
            ->values()
            ->map(fn ($category, $i) => [
                $i + 1,
                $category->name,
                $category->description ?? '-',
                $category->products_count,
            ]);
    }
}
