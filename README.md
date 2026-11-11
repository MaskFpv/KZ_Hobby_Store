# KZ Hobby Store — POS Toko Hobi & Merchandise Anime

Dua aplikasi dalam satu project Laravel untuk toko action figure, gunpla, manga, dan merchandise anime:

- **Website pelanggan** di `/` (tema *Neo-Tokyo Night*): katalog, keranjang, checkout ambil-di-toko, pembayaran online
  *prototype* (QRIS / Virtual Account / Kartu), pre-order dengan DP, akun member, chat dengan CS.
- **Panel staf** di `/admin` (tema *Sakura Night*): kasir POS, pesanan online, pre-order, stok, laporan, chat pelanggan, 6 role.

Diagram alur (peta halaman, use case, activity, ERD, LRS): buka `docs/diagrams/kz-hobby-store.drawio`
di [draw.io / diagrams.net](https://app.diagrams.net) atau ekstensi VS Code *Draw.io Integration*.

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

Buka `http://127.0.0.1:8000` (website) dan `http://127.0.0.1:8000/admin/login` (panel).

Akun bawaan (dari `UserSeeder` & `CustomerSeeder`, ganti sebelum dipakai sungguhan):

| Role / jenis              | Email               | Password    | Bisa apa |
|---------------------------|---------------------|-------------|----------|
| super-admin               | admin@gmail.com     | admin123    | Semua, termasuk user / role / permission |
| admin (manajer)           | manajer@gmail.com   | manajer123  | Semua operasional + laporan & laba |
| keuangan                  | keuangan@gmail.com  | keuangan123 | Dashboard, laporan & laba, batal/refund, pembayaran online |
| gudang                    | gudang@gmail.com    | gudang123   | Produk, kategori, brand, stok masuk, opname, siapkan pesanan |
| cs (order online)         | cs@gmail.com        | cstoko123   | Pesanan online, pre-order, pelanggan, chat pelanggan |
| kasir                     | kasir@gmail.com     | kasir123    | Kasir POS, transaksi miliknya, pre-order, serah terima |
| member website            | ayu@example.com     | member123   | Login di `/masuk`: PO, riwayat pesanan, chat CS |

**Penjadwal (opsional):** tagihan online yang lewat batas otomatis kadaluarsa saat halaman pesanan/pembayaran
dibuka. Agar juga berjalan di latar belakang, jalankan `php artisan schedule:work` (perintah `store:expire-payments`, tiap 5 menit).

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

## Fitur website pelanggan

| Halaman | Isi |
|---------|-----|
| Beranda, Katalog, Detail Produk | Animasi GSAP/Lenis, filter kategori/brand/tipe/harga, produk unggulan |
| Keranjang, Checkout | Tamu boleh belanja; stok dicek ulang & dikunci saat checkout; ambil di toko |
| Pembayaran | **Prototype** QRIS / Virtual Account / Kartu (cek Luhn; hanya merek + 4 digit akhir disimpan), batas 24 jam |
| Status & Lacak Pesanan | Timeline status + QR kode pengambilan; tamu melacak dengan kode + email |
| Pre-Order | Wajib login member, DP minimal 30%, cicilan/pelunasan online dari halaman Akun |
| Daftar / Masuk / Akun | Captcha buatan sendiri (GD) + persetujuan S&K; riwayat pesanan & PO, ganti profil/password |
| Chat CS | Widget mengambang (khusus member): teks, gambar maks 2 MB, lampiran produk/pesanan/PO, centang dibaca |

## Fitur panel staf

| Menu | Isi |
|------|-----|
| Home | Sapaan ala Jepang sesuai jam & role (`-sama` untuk admin, `-san` untuk kasir), peribahasa harian, aksi cepat, PO siap diambil |
| Dashboard | Transaksi & omset hari ini, omset bulan ini vs bulan lalu, laba kotor (admin), grafik bulanan per tahun, penjualan per kategori, produk terlaris, stok menipis |
| Kasir (POS) | Keranjang, filter kategori/brand, diskon, tunai/debit/QRIS/transfer, kembalian, nota |
| Riwayat Transaksi / Laporan | Filter periode & metode bayar, export PDF/XLS |
| Pesanan Online | Dibayar → disiapkan → siap diambil → serah terima (jadi transaksi channel *online*), batal + refund, scan/ketik kode langsung buka detail |
| Pembayaran Online | Semua tagihan online (pesanan & PO), filter status/metode, export XLS |
| Chat Pelanggan | Inbox percakapan member, badge belum dibaca, balas teks/gambar, rekomendasikan produk |
| Manajemen Pre-Order | DP → cicilan → barang tiba → pelunasan (otomatis jadi transaksi), nota PO, pembatalan (keuangan/admin), badge PO online |
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
  Http/Controllers/   Satu controller per menu panel; Store/ = controller website pelanggan
  Http/Livewire/      Dashboard, Kasir (Transaction/Index), Laporan, Admin/ChatInbox, Store/ChatWidget
  Http/Requests/      Validasi form
  Models/             Eloquent model + konstanta label (status, kondisi, metode bayar)
  Policies/           Hak akses per model, nama permission "<aksi> <subjek>"
  Services/           Logika bisnis: Stock, PreOrder, Order, Payment (prototype), Cart, Chat
  Support/            Menu (config/menu.php), Greeting (sapaan Jepang), Roles, Captcha
config/
  menu.php            Daftar menu sidebar  <- tambah menu baru di sini
  store.php           Nama, alamat, jam buka, DP minimal (%), batas bayar (jam)
docs/
  diagrams/           kz-hobby-store.drawio: peta halaman, use case, activity, ERD, LRS
  specs/              Desain fitur website & role
database/
  migrations/         Skema tabel (2026_11_09_* = skema toko hobi)
  seeders/            Data awal: permission, user, kategori, brand, produk, pelanggan
lang/id/              Teks Bahasa Indonesia (validasi, login, teks umum)
public/
  css/app.css         Tema panel "Sakura Night" (token warna di :root)
  css/store.css       Tema website "Neo-Tokyo Night"
  js/app.js           Panel: sidebar, konfirmasi hapus, DataTables
  js/store.js         Website: animasi, keranjang, timer bayar, QR
  js/chat.js          Chat (website & panel): Enter kirim, auto-scroll
  img/logo.svg        Logo toko
  import/             Template Excel untuk import
resources/views/
  layouts/            app.blade.php (panel) + store.blade.php (website)
  app/<modul>/        View per menu panel (index, create, edit, form-inputs, data = PDF)
  store/              Halaman website pelanggan
  pdf/                Kop & style bersama untuk semua PDF
  livewire/           View komponen Livewire
tests/Feature/        Test otomatis per fase; PageSmokeTest membuka semua halaman untuk tiap role
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
- Pesanan online memotong stok saat checkout; stok kembali bila tagihan kadaluarsa atau pesanan dibatalkan.
  Omset pesanan online dicatat saat **serah terima** di toko.
- PO online baru masuk antrean barang setelah DP dibayar; DP yang tidak dibayar sampai batas membatalkan PO.
- Pembayaran online hanya **simulasi**: tidak ada uang sungguhan, nomor kartu & CVV tidak pernah disimpan.
- Sesi pelanggan (guard `customer`) dan staf (guard `web`) terpisah: member tidak bisa membuka panel.
- Gambar chat disimpan privat (`storage/app/chat`) dan hanya bisa dibuka pemilik percakapan atau staf berizin.
- Zona waktu aplikasi: **Asia/Jakarta (WIB)**.
