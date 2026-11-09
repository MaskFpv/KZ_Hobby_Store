# KZ Hobby Store — POS Toko Hobi & Merchandise Anime

Panel internal (admin & kasir) untuk toko action figure, gunpla, manga, dan merchandise anime.
Tidak ada halaman publik: semua halaman butuh login.

**Stack:** Laravel 10 · PHP 8.1+ · MySQL · Livewire 2 · Spatie Permission · DomPDF · Maatwebsite Excel
**Frontend:** Bootstrap 5 + Bootstrap Icons (CDN), tanpa build step (tidak perlu `npm`).

---

## Menjalankan project

```bash
composer install
cp .env.example .env          # lalu sesuaikan DB_DATABASE dst.
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link      # agar foto produk / logo brand tampil
php artisan serve
```

Akun bawaan (dari `database/seeders/UserSeeder.php`, ganti sebelum dipakai sungguhan):

| Role        | Email             | Password   |
|-------------|-------------------|------------|
| super-admin | admin@gmail.com   | admin123   |
| kasir       | kasir@gmail.com   | kasir123   |

## Menjalankan test

Test memakai database terpisah **`kz_coffee_test`** (lihat `phpunit.xml`), buat dulu sekali:

```sql
CREATE DATABASE kz_coffee_test;
```

```bash
php artisan test
```

> `tests/TestCase.php` membatalkan test bila database bukan `*_test`, supaya data development tidak terhapus.

---

## Fitur

| Menu | Isi |
|------|-----|
| Home | Sapaan ala Jepang sesuai jam & role (`-sama` untuk admin, `-san` untuk kasir), peribahasa harian, aksi cepat, PO siap diambil |
| Dashboard | Transaksi & omset hari ini, omset bulan ini vs bulan lalu, laba kotor (admin), grafik bulanan per tahun, penjualan per kategori, produk terlaris, stok menipis |
| Kasir (POS) | Keranjang, filter kategori/brand, diskon, tunai/debit/QRIS/transfer, kembalian, nota |
| Riwayat Transaksi / Laporan | Filter periode & metode bayar, export PDF/XLS |
| Manajemen Pre-Order | DP → cicilan → barang tiba → pelunasan (otomatis jadi transaksi), nota PO, pembatalan (admin) |
| Katalog Produk, Kategori, Brand, Pelanggan | CRUD + export PDF/XLS + import XLS (template di `public/import`) |
| Stok Masuk | Dipesan → dikirim → diterima (stok bertambah, PO yang menunggu otomatis dialokasikan) |
| Stok Opname | Input stok fisik banyak produk sekaligus, selisih tercatat |
| User, Role, Permission | Satu user satu role; role `super-admin` & permission bawaan sistem dikunci |

---

## Struktur folder (yang penting saja)

```
app/
  Exports/            Export Excel. Turunan BaseSheetExport (kop, header, format Rupiah otomatis)
  Imports/            Import Excel. Turunan BaseRowImport (baris salah dilewati + dilaporkan)
  Http/Controllers/   Satu controller per menu
  Http/Livewire/      Komponen interaktif: Dashboard, Kasir (Transaction/Index), Laporan
  Http/Requests/      Validasi form
  Models/             Eloquent model + konstanta label (status, kondisi, metode bayar)
  Policies/           Hak akses per model, nama permission "<aksi> <subjek>"
  Services/           Logika stok & pre-order (StockService, PreOrderService)
  Support/            Menu (baca config/menu.php) & Greeting (sapaan Jepang)
config/
  menu.php            Daftar menu sidebar  <- tambah menu baru di sini
  store.php           Nama, tagline, alamat toko (bisa lewat .env STORE_*)
database/
  migrations/         Skema tabel (2026_11_09_* = skema toko hobi)
  seeders/            Data awal: permission, user, kategori, brand, produk, pelanggan
lang/id/              Teks Bahasa Indonesia (validasi, login, teks umum)
public/
  css/app.css         Tema warna "Sakura Night" (token warna di :root)
  js/app.js           Sidebar, konfirmasi hapus, DataTables
  img/logo.svg        Logo toko
  import/             Template Excel untuk import
resources/views/
  layouts/            app.blade.php + partials/sidebar & topbar
  app/<modul>/        View per menu (index, create, edit, form-inputs, data = PDF)
  pdf/                Kop & style bersama untuk semua PDF
  livewire/           View komponen Livewire
tests/Feature/        Test otomatis per fase
```

## Resep cepat

**Menambah menu baru**
1. Buat route di `routes/web.php` (misal `Route::resource('suppliers', SupplierController::class);`).
2. Tambah satu baris di `config/menu.php`:
   `['route' => 'suppliers.index', 'active' => 'suppliers.*', 'label' => 'Supplier', 'icon' => 'bi-shop', 'can' => ['view-any', App\Models\Supplier::class]],`
3. Buat policy `app/Policies/SupplierPolicy.php` dan tambahkan subjek `suppliers` di `database/seeders/PermissionsSeeder.php`.

**Mengganti warna tema** — ubah variabel `--kz-*` di bagian atas `public/css/app.css`.

**Mengganti nama toko** — isi `STORE_NAME`, `STORE_TAGLINE`, `STORE_ADDRESS` di `.env`.

**Menambah export Excel** — buat class turunan `App\Exports\BaseSheetExport`, isi `title()`, `headings()`, `rows()`.

**Menambah import Excel** — buat class turunan `App\Imports\BaseRowImport`, isi `handleRow()`, lalu di controller:
`return $this->runImport($request, new XxxImport, 'xxx.index');` (pakai trait `HandlesExcelImport`).

## Aturan bisnis penting

- Stok hanya berubah lewat **Kasir**, **Stok Masuk (diterima)**, **Stok Opname**, dan **pelunasan PO**. Form edit produk tidak bisa mengubah stok.
- Unit yang sudah dialokasikan ke PO berstatus *Barang Tiba* tidak bisa dijual di Kasir.
- Antrean PO dialokasikan berurutan (pesanan paling lama dulu).
- Omset PO dicatat saat **pelunasan**, bukan saat DP.
- Harga & nama produk di detail transaksi disimpan sebagai *snapshot*: nota lama tidak berubah walau produk diedit/dihapus.
- Zona waktu aplikasi: **Asia/Jakarta (WIB)**.
