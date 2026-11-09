<?php

namespace App\Http\Livewire\Transaction;

use App\Http\Controllers\TransactionController;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/** Laporan penjualan per periode + ringkasan. Default: bulan berjalan. */
class Laporan extends Component
{
    public $start_date;
    public $end_date;

    protected $queryString = ['start_date' => ['as' => 'from'], 'end_date' => ['as' => 'to']];

    public function mount()
    {
        $this->start_date ??= now()->startOfMonth()->toDateString();
        $this->end_date ??= now()->toDateString();
    }

    /** Tombol periode cepat. */
    public function setRange(string $range)
    {
        [$this->start_date, $this->end_date] = match ($range) {
            'today' => [now()->toDateString(), now()->toDateString()],
            '7d' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };
    }

    public function render()
    {
        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [], ['start_date' => 'tanggal mulai', 'end_date' => 'tanggal akhir']);

        $transactions = TransactionController::filteredQuery(['from' => $this->start_date, 'to' => $this->end_date])->get();

        $summary = [
            'count' => $transactions->count(),
            'omset' => (float) $transactions->sum('total_price'),
            'discount' => (float) $transactions->sum('discount'),
            'items' => 0,
            'profit' => null,
        ];

        $details = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.date', [$this->start_date, $this->end_date]);

        $summary['items'] = (int) (clone $details)->sum('transaction_details.qty');

        if (auth()->user()->isSuperAdmin()) {
            $margin = (float) (clone $details)->sum(DB::raw('transaction_details.sub_total - transaction_details.purchase_price * transaction_details.qty'));
            $summary['profit'] = $margin - $summary['discount'];
        }

        return view('livewire.transaction.laporan', compact('transactions', 'summary'));
    }
}
