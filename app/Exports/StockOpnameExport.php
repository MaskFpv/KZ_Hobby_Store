<?php

namespace App\Exports;

use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Collection;

class StockOpnameExport extends BaseSheetExport
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Riwayat Stok Opname';
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'SKU', 'Produk', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Catatan', 'Petugas'];
    }

    protected function rows(): Collection
    {
        return StockOpnameController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($o, $i) => [
                $i + 1,
                $o->opname_date->format('d-m-Y'),
                $o->product->sku ?? '-',
                $o->product->name ?? '-',
                $o->system_stock,
                $o->physical_stock,
                $o->difference,
                $o->notes ?? '-',
                $o->user->name ?? '-',
            ]);
    }
}
