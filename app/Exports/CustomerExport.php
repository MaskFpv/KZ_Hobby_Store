<?php

namespace App\Exports;

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Collection;

class CustomerExport extends BaseSheetExport
{
    protected function title(): string
    {
        return 'Data Pelanggan / Member';
    }

    public function headings(): array
    {
        return ['No', 'Kode Member', 'Nama', 'Telepon', 'Email', 'Alamat', 'Jumlah Transaksi', 'Total Belanja'];
    }

    protected function currencyColumns(): array
    {
        return ['H'];
    }

    protected function rows(): Collection
    {
        return CustomerController::listQuery()->get()
            ->values()
            ->map(fn ($c, $i) => [
                $i + 1,
                $c->kode_member,
                $c->nama,
                $c->telepon ?? '-',
                $c->email ?? '-',
                $c->alamat ?? '-',
                $c->transactions_count,
                (float) ($c->total_belanja ?? 0),
            ]);
    }
}
