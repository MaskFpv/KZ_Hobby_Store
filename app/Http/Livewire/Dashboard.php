<?php

namespace App\Http\Livewire;

use App\Models\Customer;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Dashboard toko. Omset = transactions.total_price (sudah dikurangi diskon).
 * Laba kotor = (harga jual - harga modal saat terjual) - diskon. Hanya untuk admin.
 */
class Dashboard extends Component
{
    public $year;

    public function mount()
    {
        $this->year = (int) now()->year;
    }

    /** Pilihan tahun di grafik berubah: kirim data baru ke Chart.js. */
    public function updatedYear()
    {
        $this->year = (int) $this->year;
        $this->dispatchBrowserEvent('dashboard-charts', $this->chartData());
    }

    public function render()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $lastMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $lastMonthSameDay = $today->copy()->subMonthNoOverflow();

        $todayQuery = Transaction::whereDate('date', $today);
        $monthOmset = $this->omset($monthStart, $today);
        // Bandingkan dengan periode yang sama bulan lalu (tgl 1 s/d tanggal hari ini)
        $lastMonthOmset = $this->omset($lastMonthStart, $lastMonthSameDay);

        $firstYear = (int) (Transaction::min(DB::raw('YEAR(date)')) ?: $today->year);

        return view('livewire.dashboard', [
            'isAdmin' => auth()->user()->isSuperAdmin(),
            'todayCount' => (clone $todayQuery)->count(),
            'todayOmset' => (float) (clone $todayQuery)->sum('total_price'),
            'monthOmset' => $monthOmset,
            'growth' => $lastMonthOmset > 0 ? round(($monthOmset - $lastMonthOmset) / $lastMonthOmset * 100, 1) : null,
            'monthProfit' => $this->grossProfit($monthStart, $today),
            'years' => range($today->year, min($firstYear, $today->year)),
            'chart' => $this->chartData(),
            'topProducts' => $this->topProducts(),
            'lowStockProducts' => Product::readyStock()->lowStock()->orderBy('stock')->limit(6)->get(['id', 'name', 'sku', 'stock']),
            'recentTransactions' => Transaction::with('customer:id,nama')->latest('date')->latest('id')->limit(6)->get(),
            'poSummary' => PreOrder::selectRaw('status, COUNT(*) as total')->whereIn('status', ['waiting', 'arrived'])->groupBy('status')->pluck('total', 'status'),
            'poOutstanding' => (float) PreOrder::whereIn('status', ['waiting', 'arrived'])->selectRaw('SUM(total_price - paid_amount) as s')->value('s'),
            'totalProducts' => Product::count(),
            'totalCustomers' => Customer::count(),
        ]);
    }

    private function omset(Carbon $from, Carbon $to): float
    {
        return (float) Transaction::whereBetween('date', [$from->toDateString(), $to->toDateString()])->sum('total_price');
    }

    private function grossProfit(Carbon $from, Carbon $to): float
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $margin = (float) TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.date', $range)
            ->sum(DB::raw('transaction_details.sub_total - transaction_details.purchase_price * transaction_details.qty'));

        return $margin - (float) Transaction::whereBetween('date', $range)->sum('discount');
    }

    /** Data grafik: omset per bulan + penjualan per kategori untuk tahun terpilih. */
    private function chartData(): array
    {
        $monthly = Transaction::selectRaw('MONTH(date) as m, SUM(total_price) as total')
            ->whereYear('date', $this->year)
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'm');

        $labels = [];
        $totals = [];
        foreach (range(1, 12) as $m) {
            $labels[] = Carbon::create($this->year, $m, 1)->translatedFormat('M');
            $totals[] = (float) ($monthly[$m] ?? 0);
        }

        $byCategory = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->leftJoin('products', 'products.id', '=', 'transaction_details.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereYear('transactions.date', $this->year)
            ->selectRaw("COALESCE(categories.name, 'Produk dihapus') as name, SUM(transaction_details.sub_total) as total")
            ->groupBy(DB::raw("COALESCE(categories.name, 'Produk dihapus')"))
            ->orderByDesc('total')
            ->get();

        return [
            'year' => $this->year,
            'labels' => $labels,
            'totals' => $totals,
            'categoryLabels' => $byCategory->pluck('name')->all(),
            'categoryTotals' => $byCategory->pluck('total')->map(fn ($v) => (float) $v)->all(),
        ];
    }

    private function topProducts()
    {
        return TransactionDetail::select(
            'product_id',
            DB::raw('MAX(product_name) as product_name'),
            DB::raw('SUM(qty) as total_qty'),
            DB::raw('SUM(sub_total) as total_sales')
        )
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product:id,image')
            ->get();
    }
}
