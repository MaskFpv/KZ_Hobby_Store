<?php

namespace App\Http\Controllers;

use App\Exports\ListTransaction;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public const FILTERS = ['from', 'to', 'payment_method'];

    public static function filteredQuery(array $filters): Builder
    {
        return Transaction::with(['customer:id,nama', 'user:id,name', 'preOrder:id,code,transaction_id'])
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filters['payment_method'] ?? null, fn ($q, $v) => $q->where('payment_method', $v))
            ->latest('date')
            ->latest('id');
    }

    /** Halaman kasir (POS). */
    public function index()
    {
        $this->authorize('create', Transaction::class);

        return view('app.transaction.index');
    }

    public function listTransaksi(Request $request)
    {
        $this->authorize('view-any', Transaction::class);

        $filters = $request->only(self::FILTERS);
        $transactions = self::filteredQuery($filters)
            // Tanpa akses laporan (mis. kasir): hanya transaksi yang dia layani
            ->when(! $request->user()->can('view-reports'), fn ($q) => $q->where('user_id', $request->user()->id))
            ->get();

        return view('app.transaction.data', compact('transactions', 'filters'));
    }

    public function laporan()
    {
        $this->authorize('view-reports');

        return view('app.transaction.laporan');
    }

    public function nota_faktur($id)
    {
        $data = Transaction::with(['customer', 'user', 'transactionDetails', 'preOrder.payments'])->findOrFail($id);

        $this->authorize('view', $data);

        return view('app.transaction.invoice', compact('data'));
    }

    public function export(Request $request)
    {
        $this->authorize('view-reports');

        return Excel::download(new ListTransaction($request->only(self::FILTERS)), date('Ymd') . '__List-Transaksi.xlsx');
    }

    public function exportpdf(Request $request)
    {
        $this->authorize('view-reports');

        $filters = $request->only(self::FILTERS);
        $data = self::filteredQuery($filters)->get();

        return Pdf::loadView('app.transaction.list', compact('data', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download('transaksi.pdf');
    }

    public function exportLaporan($awal, $akhir)
    {
        $this->authorize('view-reports');

        $laporan = self::filteredQuery(['from' => $awal, 'to' => $akhir])->reorder('date')->orderBy('id')->get();
        $total_pendapatan = $laporan->sum('total_price');
        $total_diskon = $laporan->sum('discount');

        $tanggalAwal = date('d-m-Y', strtotime($awal));
        $tanggalAkhir = date('d-m-Y', strtotime($akhir));

        return Pdf::loadView('app.transaction.pdf', compact('laporan', 'total_pendapatan', 'total_diskon', 'tanggalAwal', 'tanggalAkhir'))
            ->setPaper('a4', 'landscape')
            ->download('Laporan Penjualan ' . $tanggalAwal . ' sd ' . $tanggalAkhir . '.pdf');
    }
}
