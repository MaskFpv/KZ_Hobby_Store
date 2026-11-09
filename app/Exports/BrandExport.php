<?php

namespace App\Exports;

use App\Models\Brand;
use Illuminate\Support\Collection;

class BrandExport extends BaseSheetExport
{
    protected function title(): string
    {
        return 'Data Brand / Pabrikan';
    }

    public function headings(): array
    {
        return ['No', 'Nama Brand', 'Negara Asal', 'Deskripsi', 'Jumlah Produk'];
    }

    protected function rows(): Collection
    {
        return Brand::withCount('products')->orderBy('name')->get()
            ->values()
            ->map(fn ($brand, $i) => [
                $i + 1,
                $brand->name,
                $brand->country ?? '-',
                $brand->description ?? '-',
                $brand->products_count,
            ]);
    }
}
