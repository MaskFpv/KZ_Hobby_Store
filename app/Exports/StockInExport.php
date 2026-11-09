<?php

namespace App\Exports;

use App\Http\Controllers\StockInController;
use Illuminate\Support\Collection;

class StockInExport extends BaseSheetExport
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Data Stok Masuk';
    }

    public function headings(): array
    {
        return ['No', 'Kode', 'Tgl Pesan', 'SKU', 'Produk', 'Supplier', 'Jumlah', 'Harga Beli', 'Total', 'Status', 'Tgl Diterima', 'Petugas'];
    }

    protected function currencyColumns(): array
    {
        return ['H', 'I'];
    }

    protected function rows(): Collection
    {
        return StockInController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($s, $i) => [
                $i + 1,
                $s->code,
                $s->ordered_at->format('d-m-Y'),
                $s->product->sku ?? '-',
                $s->product->name ?? '-',
                $s->supplier ?? '-',
                $s->quantity,
                (float) $s->purchase_price,
                (float) $s->purchase_price * $s->quantity,
                $s->status_label,
                $s->received_at?->format('d-m-Y H:i') ?? '-',
                $s->user->name ?? '-',
            ]);
    }
}
