<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;

class UserExport extends BaseSheetExport
{
    protected function title(): string
    {
        return 'Data User';
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Email', 'Role', 'Jumlah Transaksi', 'Dibuat'];
    }

    protected function rows(): Collection
    {
        return User::with('roles')->withCount('transactions')->orderBy('name')->get()
            ->values()
            ->map(fn ($u, $i) => [
                $i + 1,
                $u->name,
                $u->email,
                $u->getRoleNames()->implode(', ') ?: '-',
                $u->transactions_count,
                $u->created_at?->format('d-m-Y'),
            ]);
    }
}
