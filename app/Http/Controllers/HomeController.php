<?php

namespace App\Http\Controllers;

use App\Models\PreOrder;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Transaction;
use App\Support\Greeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Halaman Home: sapaan Jepang per role, ringkasan singkat, dan aksi cepat.
 * Admin melihat angka seluruh toko; kasir melihat transaksinya sendiri.
 */
class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $greeting = Greeting::for($user);
        $today = now()->toDateString();

        $trxToday = Transaction::whereDate('date', $today)
            ->when(! $greeting['sees_all'], fn ($q) => $q->where('user_id', $user->id));

        $stats = [
            'trx_count' => (clone $trxToday)->count(),
            'trx_total' => (float) (clone $trxToday)->sum('total_price'),
            'po_ready' => PreOrder::where('status', 'arrived')->count(),
            'low_stock' => Product::readyStock()->lowStock()->count(),
            'pending_stock_in' => StockIn::whereIn('status', ['ordered', 'shipped'])->count(),
            'po_overdue' => PreOrder::where('status', 'waiting')->whereDate('estimated_arrival', '<', $today)->count(),
        ];

        // PO yang barangnya sudah tiba: kasir perlu menghubungi member
        $readyPreOrders = $user->can('view-any', PreOrder::class)
            ? PreOrder::with(['customer:id,nama,telepon', 'product:id,name'])
                ->where('status', 'arrived')->orderBy('order_date')->limit(5)->get()
            : collect();

        return view('home', [
            'greeting' => $greeting,
            'stats' => $stats,
            'readyPreOrders' => $readyPreOrders,
            'actions' => $this->quickActions($user),
        ]);
    }

    /** Aksi cepat sesuai izin user. Item tanpa izin otomatis tidak tampil. */
    private function quickActions($user): array
    {
        $all = [
            ['route' => 'transaction.index', 'icon' => 'bi-calculator', 'tone' => 'tone-sakura', 'label' => 'Buka Kasir', 'hint' => 'Mulai transaksi baru', 'can' => ['create', Transaction::class]],
            ['route' => 'pre-orders.create', 'icon' => 'bi-hourglass-split', 'tone' => 'tone-lavender', 'label' => 'Buat Pre-Order', 'hint' => 'Catat pesanan & DP', 'can' => ['create', PreOrder::class]],
            ['route' => 'customers.create', 'icon' => 'bi-person-plus', 'tone' => 'tone-sky', 'label' => 'Member Baru', 'hint' => 'Daftarkan kolektor', 'can' => ['create', \App\Models\Customer::class]],
            ['route' => 'online-orders.index', 'icon' => 'bi-globe2', 'tone' => 'tone-sakura', 'label' => 'Pesanan Online', 'hint' => 'Siapkan & serahkan', 'can' => ['view-any', \App\Models\Order::class]],
            ['route' => 'chats.index', 'icon' => 'bi-chat-heart', 'tone' => 'tone-lavender', 'label' => 'Chat Pelanggan', 'hint' => 'Balas pertanyaan member', 'can' => ['view-any', \App\Models\Conversation::class]],
            ['route' => 'products.index', 'icon' => 'bi-search', 'tone' => 'tone-matcha', 'label' => 'Cek Katalog', 'hint' => 'Harga & stok produk', 'can' => ['view-any', Product::class]],
            ['route' => 'dashboard.index', 'icon' => 'bi-speedometer2', 'tone' => 'tone-yuzu', 'label' => 'Dashboard', 'hint' => 'Ringkasan toko', 'can' => ['view-dashboard', []]],
            ['route' => 'stock-ins.create', 'icon' => 'bi-truck', 'tone' => 'tone-sky', 'label' => 'Catat Stok Masuk', 'hint' => 'Restock dari supplier', 'can' => ['create', StockIn::class]],
            ['route' => 'stock-opnames.create', 'icon' => 'bi-clipboard-check', 'tone' => 'tone-matcha', 'label' => 'Stok Opname', 'hint' => 'Cocokkan stok fisik', 'can' => ['create', \App\Models\StockOpname::class]],
            ['route' => 'transaction.laporan', 'icon' => 'bi-file-earmark-bar-graph', 'tone' => 'tone-yuzu', 'label' => 'Laporan', 'hint' => 'Rekap per periode', 'can' => ['view-reports', []]],
        ];

        return array_values(array_filter(
            $all,
            fn ($a) => Route::has($a['route']) && $user->can($a['can'][0], $a['can'][1])
        ));
    }
}
