<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - POS Toko Hobi & Merchandise Anime (panel internal)
|--------------------------------------------------------------------------
| Tidak ada frontend publik. Hanya login/logout; akun dibuat admin lewat menu User.
| Urutan grup di bawah mengikuti menu sidebar.
*/

Route::redirect('/', '/home');

Auth::routes(['register' => false, 'reset' => false, 'confirm' => false, 'verify' => false]);

Route::middleware('auth')->group(function () {
    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    /* ---------- Master Data ---------- */
    Route::resource('categories', CategoryController::class)->except('show');
    Route::get('categories-export', [CategoryController::class, 'export'])->name('categories-export');
    Route::get('category/exportpdf', [CategoryController::class, 'exportpdf'])->name('categoires-exportPdf');
    Route::post('categories-import', [CategoryController::class, 'import'])->name('categories-import');

    Route::resource('brands', BrandController::class);
    Route::get('brands-export', [BrandController::class, 'export'])->name('brands-export');
    Route::get('brand/exportpdf', [BrandController::class, 'exportpdf'])->name('brands-exportPdf');
    Route::post('brands-import', [BrandController::class, 'import'])->name('brands-import');

    Route::resource('products', ProductController::class);
    Route::get('products-export', [ProductController::class, 'export'])->name('products-export');
    Route::get('product/exportpdf', [ProductController::class, 'exportpdf'])->name('products-exportPdf');
    Route::post('products-import', [ProductController::class, 'import'])->name('products-import');

    Route::resource('customers', CustomerController::class);
    Route::get('customer-export', [CustomerController::class, 'export'])->name('customer-export');
    Route::get('customer/exportpdf', [CustomerController::class, 'exportpdf'])->name('customer-exportPdf');
    Route::post('customer-import', [CustomerController::class, 'import'])->name('customer-import');

    /* ---------- Inventaris & Stok ---------- */
    Route::resource('stock-ins', StockInController::class);
    Route::post('stock-ins/{stockIn}/status', [StockInController::class, 'changeStatus'])->name('stock-ins.status');
    Route::get('stock-ins-export', [StockInController::class, 'export'])->name('stock-ins-export');
    Route::get('stock-in/exportpdf', [StockInController::class, 'exportpdf'])->name('stock-ins-exportPdf');
    Route::post('stock-ins-import', [StockInController::class, 'import'])->name('stock-ins-import');

    Route::resource('stock-opnames', StockOpnameController::class)->only(['index', 'create', 'store']);
    Route::get('stock-opnames-export', [StockOpnameController::class, 'export'])->name('stock-opnames-export');
    Route::get('stock-opname/exportpdf', [StockOpnameController::class, 'exportpdf'])->name('stock-opnames-exportPdf');

    /* ---------- Transaksi & Penjualan ---------- */
    Route::get('transaction', [TransactionController::class, 'index'])->name('transaction.index');
    Route::get('transaction/index', [TransactionController::class, 'listTransaksi'])->name('transaction.data');
    Route::get('transaction/laporan', [TransactionController::class, 'laporan'])->name('transaction.laporan');
    Route::get('transaction/invoice/{id}', [TransactionController::class, 'nota_faktur'])->name('transaction.invoice');
    Route::get('list-export', [TransactionController::class, 'export'])->name('list-export');
    Route::get('transaction-list/exportpdf', [TransactionController::class, 'exportpdf'])->name('transaction-list-exportPdf');
    Route::get('laporan/exportpdf/{awal}/{akhir}', [TransactionController::class, 'exportLaporan'])->name('laporan-exportPdf');

    // Pre-Order (tanpa hapus: pakai pembatalan agar riwayat pembayaran tetap ada)
    Route::resource('pre-orders', PreOrderController::class)->except('destroy');
    Route::post('pre-orders/{preOrder}/payments', [PreOrderController::class, 'storePayment'])->name('pre-orders.payments.store');
    Route::post('pre-orders/{preOrder}/arrive', [PreOrderController::class, 'markArrived'])->name('pre-orders.arrive');
    Route::post('pre-orders/{preOrder}/complete', [PreOrderController::class, 'complete'])->name('pre-orders.complete');
    Route::post('pre-orders/{preOrder}/cancel', [PreOrderController::class, 'cancel'])->name('pre-orders.cancel');
    Route::get('pre-orders/{preOrder}/receipt', [PreOrderController::class, 'receipt'])->name('pre-orders.receipt');
    Route::get('pre-orders-export', [PreOrderController::class, 'export'])->name('pre-orders-export');
    Route::get('pre-order/exportpdf', [PreOrderController::class, 'exportpdf'])->name('pre-orders-exportPdf');

    /* ---------- Pengaturan Akses ---------- */
    Route::resource('users', UserController::class);
    Route::get('user-export', [UserController::class, 'export'])->name('user-export');
    Route::get('user/exportpdf', [UserController::class, 'exportpdf'])->name('user-exportPdf');
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
});
