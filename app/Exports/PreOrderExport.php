<?php

namespace App\Exports;

use App\Http\Controllers\PreOrderController;
use Illuminate\Support\Collection;

class PreOrderExport extends BaseSheetExport
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Data Pre-Order';
    }

    public function headings(): array
    {
        return ['No', 'Kode PO', 'Tgl Pesan', 'Kode Member', 'Pelanggan', 'Telepon', 'Produk', 'Qty', 'Total', 'Dibayar', 'Sisa', 'Estimasi Tiba', 'Status'];
    }

    protected function currencyColumns(): array
    {
        return ['I', 'J', 'K'];
    }

    protected function rows(): Collection
    {
        return PreOrderController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($po, $i) => [
                $i + 1,
                $po->code,
                $po->order_date->format('d-m-Y'),
                $po->customer->kode_member ?? '-',
                $po->customer->nama ?? '-',
                $po->customer->telepon ?? '-',
                $po->product->name ?? '-',
                $po->quantity,
                (float) $po->total_price,
                (float) $po->paid_amount,
                $po->remaining_amount,
                $po->estimated_arrival?->format('d-m-Y') ?? '-',
                $po->status_label,
            ]);
    }
}
