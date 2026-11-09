<?php

/*
|--------------------------------------------------------------------------
| Menu sidebar
|--------------------------------------------------------------------------
| Satu tempat untuk mengatur menu. Dipakai oleh sidebar, judul halaman di
| topbar, dan aksi cepat di Home.
|
| route  : nama route index menu. Item otomatis disembunyikan bila route belum ada.
| active : pola routeIs() supaya menu tetap aktif di halaman create/edit/show.
| can    : [ability, model class] dicek dengan Gate (policy). Kosongkan = semua user.
| icon   : nama ikon Bootstrap Icons (https://icons.getbootstrap.com).
|
| Menambah halaman baru = tambah satu baris di sini.
*/

return [
    [
        'title' => null, // tanpa judul grup
        'items' => [
            ['route' => 'home', 'active' => 'home', 'label' => 'Home', 'icon' => 'bi-house-heart'],
            ['route' => 'dashboard.index', 'active' => 'dashboard.*', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'can' => ['view-dashboard']],
        ],
    ],
    [
        'title' => 'Transaksi & Penjualan',
        'items' => [
            ['route' => 'transaction.index', 'active' => 'transaction.index', 'label' => 'Kasir (POS)', 'icon' => 'bi-calculator', 'can' => ['create', App\Models\Transaction::class]],
            ['route' => 'transaction.data', 'active' => ['transaction.data', 'transaction.invoice'], 'label' => 'Riwayat Transaksi', 'icon' => 'bi-receipt', 'can' => ['view-any', App\Models\Transaction::class]],
            ['route' => 'pre-orders.index', 'active' => 'pre-orders.*', 'label' => 'Manajemen Pre-Order', 'icon' => 'bi-hourglass-split', 'can' => ['view-any', App\Models\PreOrder::class]],
            ['route' => 'transaction.laporan', 'active' => 'transaction.laporan', 'label' => 'Laporan Penjualan', 'icon' => 'bi-file-earmark-bar-graph', 'can' => ['view-any', App\Models\Transaction::class]],
        ],
    ],
    [
        'title' => 'Master Data',
        'items' => [
            ['route' => 'products.index', 'active' => 'products.*', 'label' => 'Katalog Produk', 'icon' => 'bi-box-seam', 'can' => ['view-any', App\Models\Product::class]],
            ['route' => 'categories.index', 'active' => 'categories.*', 'label' => 'Kategori Produk', 'icon' => 'bi-tags', 'can' => ['view-any', App\Models\Category::class]],
            ['route' => 'brands.index', 'active' => 'brands.*', 'label' => 'Brand / Pabrikan', 'icon' => 'bi-award', 'can' => ['view-any', App\Models\Brand::class]],
            ['route' => 'customers.index', 'active' => 'customers.*', 'label' => 'Pelanggan / Member', 'icon' => 'bi-people', 'can' => ['view-any', App\Models\Customer::class]],
        ],
    ],
    [
        'title' => 'Inventaris & Stok',
        'items' => [
            ['route' => 'stock-ins.index', 'active' => 'stock-ins.*', 'label' => 'Stok Masuk', 'icon' => 'bi-truck', 'can' => ['view-any', App\Models\StockIn::class]],
            ['route' => 'stock-opnames.index', 'active' => 'stock-opnames.*', 'label' => 'Stok Opname', 'icon' => 'bi-clipboard-check', 'can' => ['view-any', App\Models\StockOpname::class]],
        ],
    ],
    [
        'title' => 'Pengaturan Akses',
        'items' => [
            ['route' => 'users.index', 'active' => 'users.*', 'label' => 'User', 'icon' => 'bi-person-badge', 'can' => ['view-any', App\Models\User::class]],
            ['route' => 'roles.index', 'active' => 'roles.*', 'label' => 'Role', 'icon' => 'bi-person-lines-fill', 'can' => ['view-any', Spatie\Permission\Models\Role::class]],
            ['route' => 'permissions.index', 'active' => 'permissions.*', 'label' => 'Permission', 'icon' => 'bi-key', 'can' => ['view-any', Spatie\Permission\Models\Permission::class]],
        ],
    ],
];
