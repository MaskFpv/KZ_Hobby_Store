# Desain: Role Baru, Storefront Pelanggan, Pesanan Online, PO Online & Chat

Tanggal: 2026-11-11 · Status: **menunggu persetujuan**

## 1. Keputusan yang sudah disepakati

| Topik | Keputusan |
|---|---|
| Role | Super Admin, Admin, Keuangan, Gudang, CS / Order Online, Kasir |
| Akun pelanggan | Opsional untuk beli ready stock (tamu lacak pakai kode + email); **wajib** untuk PO dan chat |
| Daftar akun | Syarat & Ketentuan wajib dicentang + captcha gambar buatan sendiri (PHP GD) |
| Pengiriman | **Ambil di toko saja** |
| Pembayaran | Prototype: QRIS, Virtual Account, Kartu Kredit/Debit. Tidak ada gateway sungguhan |
| Chat | Polling 3 detik (Livewire), teks + gambar, lampirkan produk/pesanan, status dibaca & badge |
| Visual storefront | **Neo-Tokyo Night**: gelap, neon pink & cyan, animasi kaya |
| Panel admin | Tetap Sakura Night, dipindah ke prefix `/admin` |

## 2. Role & hak akses

Permission tetap format `<aksi> <subjek>`. Subjek baru: `orders` (pesanan online), `chats`, `payments`.
Permission khusus baru:
- `view reports` — halaman Laporan Penjualan, grafik penjualan bulanan & per kategori, export transaksi.
- `view profit` — harga modal & laba kotor.

| Area | Super Admin | Admin | Keuangan | Gudang | CS | Kasir |
|---|---|---|---|---|---|---|
| Dashboard | penuh | penuh | penuh | stok & pesanan disiapkan | pesanan & chat | — (cukup Home) |
| Laporan / grafik / laba | ✓ | ✓ | ✓ | — | — | — |
| Kasir (POS) | ✓ | ✓ | — | — | — | ✓ |
| Riwayat transaksi | semua | semua | semua | — | — | **miliknya sendiri** |
| Produk / kategori / brand | CRUD | CRUD | lihat | CRUD | lihat | lihat |
| Stok masuk / opname | ✓ | ✓ | lihat stok masuk | ✓ | — | — |
| Pelanggan | CRUD | CRUD | lihat | — | tambah/ubah | tambah/ubah |
| Pre-Order | ✓ | ✓ | lihat + batal/refund | lihat | buat/bayar | buat/bayar |
| Pesanan online | ✓ | ✓ | lihat + batal/refund | siapkan → siap diambil | proses → siap diambil | serah terima (diambil) |
| Pembayaran online | ✓ | ✓ | ✓ | — | lihat | — |
| Chat pelanggan | ✓ | ✓ | — | — | ✓ | — |
| User / Role / Permission | ✓ | — | — | — | — | — |

Dashboard dipecah per bagian dan tiap bagian dicek dengan `@can`, jadi satu halaman melayani semua role.

## 3. Storefront (website pelanggan)

### Halaman
| URL | Isi |
|---|---|
| `/` | Hero kanji besar + produk unggulan berputar, marquee brand, kategori, "Baru Datang", "Pre-Order Dibuka" (deadline), alur PO 4 langkah, CTA daftar member |
| `/katalog` | Filter kategori/brand/kondisi/tipe/harga, urutkan, cari, grid beranimasi |
| `/produk/{slug}` | Foto + zoom, detail, stok, Beli / Pre-Order (DP), "Tanya CS", produk terkait |
| `/pre-order` | Semua produk PO, countdown deadline, penjelasan DP & pelunasan |
| `/keranjang`, `/checkout` | Keranjang (ready stock), data pemesan, pilih metode bayar, setuju S&K |
| `/bayar/{kode}` | Layar pembayaran prototype per metode + hitung mundur |
| `/pesanan/{kode}` | Timeline status (signed URL untuk tamu) · `/lacak` form kode + email |
| `/akun/*` | Ringkasan, pesanan saya, PO saya (bayar cicilan/pelunasan online), profil, chat |
| `/daftar`, `/masuk`, `/keluar` | Auth pelanggan (guard `customer`) |
| `/syarat-ketentuan`, `/tentang` | S&K, lokasi & jam buka toko |

### Teknologi (tetap tanpa npm)
Blade + CSS/JS statis (`public/css/store.css`, `public/js/store.js`) + CDN:
GSAP 3 + ScrollTrigger (reveal, parallax, page transition), Lenis (smooth scroll), Alpine.js (interaksi kecil), qrcode.js (QRIS).
Font: **Dela Gothic One** (judul, mendukung kanji) + **Zen Kaku Gothic New** (isi) + **JetBrains Mono** (kode & harga).
Semua animasi mati saat `prefers-reduced-motion`.

### Animasi utama
Tirai transisi antar halaman · teks kanji raksasa yang ter-reveal per huruf · kartu produk tilt 3D + glow neon saat hover ·
marquee brand tak berujung · "terbang ke keranjang" saat tambah produk · timeline status pesanan yang terisi · grain + scanline halus.

## 4. Data & alur baru

### Tabel baru / perubahan
- `customers`: + `password`, `remember_token`, `terms_accepted_at`, email unik. Model `Customer` jadi `Authenticatable`.
- `products`: + `is_published`, `is_featured`, `po_deadline` (date), `release_estimate` (teks, misal "Mei 2027").
- `orders`: kode `ORD-YYYYMMDD-0001`, customer (nullable) + snapshot nama/email/telepon, status, total, metode, batas bayar, waktu bayar/siap/diambil, `transaction_id`.
- `order_items`: snapshot nama & harga.
- `online_payments` (polimorfik ke `orders` / `pre_orders`): metode, bank & nomor VA / payload QRIS / merek + 4 digit kartu, nominal, status, kadaluarsa.
- `pre_orders.status` + `pending_payment`; `transactions` + kolom `channel` (`pos` / `online`) dan metode `va`, `card`.
- `conversations` & `chat_messages` (pengirim pelanggan/staf, teks, gambar, produk/pesanan terlampir, `read_at`).

### Alur pesanan ready stock
`pending_payment` → (bayar) `paid` → `processing` (CS/Gudang menyiapkan) → `ready_pickup` → `completed` (kasir serah terima, **di sini Transaction dibuat**)
Cabang: belum bayar lewat batas → `expired`; dibatalkan → `cancelled` (+ tandai refund bila sudah bayar).
**Stok dipotong saat pesanan dibuat** (agar tidak terjual dobel di kasir) dan dikembalikan bila `expired`/`cancelled`.

### Alur PO online
Member pilih produk PO → bayar DP (minimal 30%, bisa lunas) → PO `pending_payment` jadi `waiting` saat DP lunas →
alur PO yang sudah ada (barang tiba, alokasi FIFO) → cicilan/pelunasan bisa dibayar online dari akun → serah terima di toko.

### Pembayaran prototype
- **QRIS**: QR dari payload palsu + tombol "Simulasikan scan & bayar".
- **Virtual Account**: pilih BCA/BNI/BRI/Mandiri, nomor VA dibuat sistem, tombol salin + "Simulasikan transfer".
- **Kartu**: form nomor (cek Luhn), masa berlaku, CVV, nama → animasi "memproses" → sukses. **Hanya merek + 4 digit terakhir yang disimpan**; nomor penuh & CVV tidak pernah disimpan. Kartu uji: `4111 1111 1111 1111`.
Tombol simulasi memanggil `PaymentService::markPaid()` — titik yang nanti diganti callback gateway sungguhan.

### Kadaluarsa
Batas bayar 24 jam (config `store.payment_due_hours`). Dicek oleh command `orders:expire` (terjadwal) **dan** secara otomatis
saat halaman pesanan dibuka, karena `php artisan serve` tidak menjalankan scheduler.

### Chat
Widget mengambang di storefront untuk member login; inbox "Chat Pelanggan" di panel untuk CS.
Polling 3 detik, gambar maks 2 MB, lampirkan produk/pesanan/PO, centang dibaca, badge belum dibaca di navbar & sidebar.

## 5. Di luar cakupan (sengaja tidak dibuat)
Pengiriman/ongkir, email/WhatsApp notifikasi, gateway sungguhan, wishlist, review produk, multi-foto per produk, reset password pelanggan (via CS).

## 6. Urutan pengerjaan & kriteria selesai
| Fase | Selesai bila |
|---|---|
| A. Role & permission, prefix `/admin` | Test hak akses tiap role lolos; kasir tidak bisa buka laporan & grafik |
| B. Akun pelanggan + storefront katalog | Daftar dengan captcha & S&K, login, katalog/detail tampil di desktop & mobile |
| C. Keranjang, checkout, pembayaran, PO online, panel Pesanan Online | Test alur bayar → siap → diambil → transaksi; stok kembali saat kadaluarsa |
| D. Chat | Pesan terkirim dua arah, gambar & lampiran, badge belum dibaca |
| E. Polish | Semua test lolos, cek browser desktop & mobile, commit & push |
