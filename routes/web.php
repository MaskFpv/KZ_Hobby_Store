<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatImageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\CustomerAuthController;
use App\Http\Controllers\Store\OrderController as StoreOrderController;
use App\Http\Controllers\Store\PaymentController as StorePaymentController;
use App\Http\Controllers\Store\PreOrderController as StorePreOrderController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Toko Hobi & Merchandise Anime
|--------------------------------------------------------------------------
| 1. Website pelanggan (storefront) di root: /, /katalog, /produk/{slug}, ...
|    Login pelanggan pakai guard "customer".
| 2. Panel staf di /admin/...: login staf pakai guard "web", akun dibuat admin lewat menu User.
|    Urutan grup panel mengikuti menu sidebar.
*/

/* ================= Website pelanggan ================= */
Route::name('store.')->group(function () {
    Route::get('/', [StoreController::class, 'home'])->name('home');
    Route::get('katalog', [StoreController::class, 'catalog'])->name('catalog');
    Route::get('produk/{slug}', [StoreController::class, 'product'])->name('product');
    Route::get('pre-order', [StoreController::class, 'preOrders'])->name('preorders');
    Route::get('syarat-ketentuan', [StoreController::class, 'terms'])->name('terms');
    Route::get('tentang', [StoreController::class, 'about'])->name('about');

    Route::middleware('guest:customer')->group(function () {
        Route::get('daftar', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('daftar', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1');
        Route::get('masuk', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('masuk', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1');
    });
    Route::get('captcha', [CustomerAuthController::class, 'captcha'])->name('captcha');

    // Belanja ready stock (tamu boleh)
    Route::get('keranjang', [CartController::class, 'index'])->name('cart');
    Route::post('keranjang', [CartController::class, 'add'])->name('cart.add');
    Route::patch('keranjang/{product}', [CartController::class, 'update'])->name('cart.update')->whereNumber('product');
    Route::delete('keranjang/{product}', [CartController::class, 'remove'])->name('cart.remove')->whereNumber('product');
    Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');

    // Status & pembayaran: pemilik (login) atau link bertanda tangan (tamu)
    Route::get('pesanan/{order}', [StoreOrderController::class, 'show'])->name('order');
    Route::get('lacak', [StoreOrderController::class, 'trackForm'])->name('track');
    Route::post('lacak', [StoreOrderController::class, 'track'])->name('track.find')->middleware('throttle:10,1');
    Route::get('bayar/{payment}', [StorePaymentController::class, 'show'])->name('pay');
    Route::post('bayar/{payment}', [StorePaymentController::class, 'confirm'])->name('pay.confirm')->middleware('throttle:20,1');

    Route::middleware('auth:customer')->group(function () {
        Route::post('keluar', [CustomerAuthController::class, 'logout'])->name('logout');
        Route::get('akun', [AccountController::class, 'index'])->name('account');
        Route::put('akun/profil', [AccountController::class, 'updateProfile'])->name('account.profile');
        Route::put('akun/password', [AccountController::class, 'updatePassword'])->name('account.password');

        // Pre-order online
        Route::get('pre-order/{slug}/pesan', [StorePreOrderController::class, 'create'])->name('po.create');
        Route::post('pre-order/{slug}/pesan', [StorePreOrderController::class, 'store'])->name('po.store')->middleware('throttle:10,1');
        Route::get('akun/pre-order/{code}', [StorePreOrderController::class, 'show'])->name('po.show');
        Route::post('akun/pre-order/{code}/bayar', [StorePreOrderController::class, 'pay'])->name('po.pay')->middleware('throttle:10,1');
    });
});

// Gambar chat: dibuka member pemilik percakapan atau staf (cek di controller, dua guard)
Route::get('chat/gambar/{message}', ChatImageController::class)->name('chat.image');

/* ================= Panel internal staf: /admin/... ================= */
Route::prefix('admin')->group(function () {
Auth::routes(['register' => false, 'reset' => false, 'confirm' => false, 'verify' => false]);

// Guard eksplisit "web": sesi pelanggan (guard customer) tidak pernah bisa masuk panel
Route::middleware('auth:web')->group(function () {
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

    // Pesanan & pembayaran online dari website
    Route::get('online-orders', [OnlineOrderController::class, 'index'])->name('online-orders.index');
    Route::get('online-orders/{order}', [OnlineOrderController::class, 'show'])->name('online-orders.show');
    Route::post('online-orders/{order}/process', [OnlineOrderController::class, 'process'])->name('online-orders.process');
    Route::post('online-orders/{order}/ready', [OnlineOrderController::class, 'ready'])->name('online-orders.ready');
    Route::post('online-orders/{order}/handover', [OnlineOrderController::class, 'handover'])->name('online-orders.handover');
    Route::post('online-orders/{order}/cancel', [OnlineOrderController::class, 'cancel'])->name('online-orders.cancel');
    Route::get('online-orders-export', [OnlineOrderController::class, 'export'])->name('online-orders-export');
    Route::get('online-order/exportpdf', [OnlineOrderController::class, 'exportpdf'])->name('online-orders-exportPdf');
    Route::get('online-payments', [OnlinePaymentController::class, 'index'])->name('online-payments.index');
    Route::get('online-payments-export', [OnlinePaymentController::class, 'export'])->name('online-payments-export');

    // Chat pelanggan (Livewire, polling 3 detik)
    Route::view('chats', 'app.chats.index')->name('chats.index')->middleware('can:view-any,App\Models\Conversation');

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
});
