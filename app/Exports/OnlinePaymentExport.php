<?php

namespace App\Exports;

use App\Http\Controllers\OnlinePaymentController;
use Illuminate\Support\Collection;

class OnlinePaymentExport extends BaseSheetExport
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Data Pembayaran Online';
    }

    public function headings(): array
    {
        return ['No', 'Kode Bayar', 'Dibuat', 'Untuk', 'Referensi', 'Metode', 'Nominal', 'Status', 'Dibayar'];
    }

    protected function currencyColumns(): array
    {
        return ['G'];
    }

    protected function rows(): Collection
    {
        return OnlinePaymentController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($p, $i) => [
                $i + 1,
                $p->code,
                $p->created_at->format('d-m-Y H:i'),
                $p->purpose_label,
                $p->payable->code ?? '-',
                $p->method_label,
                (float) $p->amount,
                $p->status_label,
                $p->paid_at?->format('d-m-Y H:i') ?? '-',
            ]);
    }
}
