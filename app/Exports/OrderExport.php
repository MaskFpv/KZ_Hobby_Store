<?php

namespace App\Exports;

use App\Http\Controllers\OnlineOrderController;
use Illuminate\Support\Collection;

class OrderExport extends BaseSheetExport
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Data Pesanan Online';
    }

    public function headings(): array
    {
        return ['No', 'Kode', 'Tanggal', 'Pemesan', 'Email', 'Telepon', 'Member', 'Jumlah Item', 'Total', 'Metode', 'Status', 'Dibayar', 'Diambil'];
    }

    protected function currencyColumns(): array
    {
        return ['I'];
    }

    protected function rows(): Collection
    {
        return OnlineOrderController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($o, $i) => [
                $i + 1,
                $o->code,
                $o->created_at->format('d-m-Y H:i'),
                $o->name,
                $o->email,
                $o->phone,
                $o->customer->kode_member ?? 'Tamu',
                $o->items_count,
                (float) $o->total,
                $o->payment_method_label,
                $o->status_label,
                $o->paid_at?->format('d-m-Y H:i') ?? '-',
                $o->completed_at?->format('d-m-Y H:i') ?? '-',
            ]);
    }
}
