<?php

namespace App\Exports;

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ListTransaction extends BaseSheetExport implements WithColumnFormatting
{
    public function __construct(private array $filters = [])
    {
    }

    protected function title(): string
    {
        return 'Data Transaksi Penjualan';
    }

    public function headings(): array
    {
        return ['No', 'No Faktur', 'Tanggal', 'Pelanggan', 'Kasir', 'Pembayaran', 'Subtotal', 'Diskon', 'Total', 'Keterangan'];
    }

    protected function currencyColumns(): array
    {
        return ['G', 'H', 'I'];
    }

    /** No faktur sebagai teks agar Excel tidak mengubah ke format ilmiah. */
    public function columnFormats(): array
    {
        return ['B' => NumberFormat::FORMAT_TEXT];
    }

    protected function rows(): Collection
    {
        return TransactionController::filteredQuery($this->filters)->get()
            ->values()
            ->map(fn ($t, $i) => [
                $i + 1,
                (string) $t->id,
                $t->date->format('d-m-Y'),
                $t->customer->nama ?? 'Pelanggan Umum',
                $t->user->name ?? '-',
                $t->payment_method_label,
                (float) $t->subtotal,
                (float) $t->discount,
                (float) $t->total_price,
                $t->keterangan ?? '-',
            ]);
    }
}
