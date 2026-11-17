# Ronde 3: Katalog instan, Artikel, Galeri Produk, Panel "Sakura Ember"

Tanggal: 2026-11-16 · Status: **selesai** · Diperbarui 2026-11-17 (revisi artikel & ronde 4 di bawah)

## Masalah & keputusan

| # | Permintaan | Keputusan |
|---|------------|-----------|
| 1 | Filter katalog me-refresh halaman | Chip/harga/urut/cari/paginasi diambil lewat `fetch` (header `X-Catalog`), hasil ditukar tanpa reload + URL ikut berubah (history). Kartu baru muncul bertahap (GSAP). Tanpa JS tetap jalan sebagai link biasa. |
| 2 | Halaman artikel | Tabel `articles`, role baru **sosmed** (Admin Sosial Media, permission `articles`). Editor **TinyMCE 7** (lisensi GPL, CDN jsDelivr): heading, gambar (upload), tabel, embed video, kutipan. HTML dibersihkan **HTMLPurifier** sebelum disimpan. Website: `/artikel` (daftar + filter kategori instan) dan `/artikel/{slug}` (daftar isi otomatis, progres baca, artikel terkait). |
| 3 | Judul terpotong di tengah kata | Animasi split-text membungkus huruf per **kata** (`white-space: nowrap`). |
| 4 | Kartu katalog susah diklik (kursor teks) | Lapisan 3D (`translateZ`) menutupi link; link kartu dibawa ke depan, isi kartu `pointer-events: none`. |
| 5 | Halaman Pre-Order terlalu sederhana & kartu meleset | Spotlight PO (pilih kartu → tampil besar), kalkulator DP interaktif (jumlah & DP → sisa tagihan), filter instan (segera tutup / kategori), urutkan; hitung mundur tidak lagi melebarkan kartu. |
| 6 | Gambar produk lebih dari satu | Tabel `product_images` (galeri, urutan, maks 8 foto). Foto utama tetap `products.image`. Detail produk: galeri + thumbnail + perbesar. |
| 7 | Desain panel admin baru | Referensi "Ember Dashboard": sidebar melayang gelap (mode ikon), topbar kaca, kartu membulat besar, gradasi sakura-koral, animasi hover/masuk. Mode terang/gelap tetap. |

## Revisi artikel (2026-11-16)

| Permintaan | Keputusan |
|------------|-----------|
| Kategori artikel bisa ditambah | Enum `articles.category` diganti tabel **`article_categories`** (nama, slug, kanji, warna, deskripsi, urutan). Menu panel baru **Kategori Artikel** (SA, Admin, Sosmed). 9 kategori bawaan; artikel lama dipetakan otomatis lewat migrasi. Kategori yang masih dipakai artikel tidak bisa dihapus. |
| Sorotan artikel kurang menarik | Kolom **`is_pinned`**: satu artikel "Sorotan" tampil besar (bento) di halaman pertama `/artikel`; menyimpan sorotan baru melepas sorotan lama. Tanpa sorotan, artikel terbaru dipakai. |
| Artikel lebih dinamis | Blok **Terpopuler** (urut `views`, bertambah tiap artikel dibuka), filter kategori & cari tanpa reload, artikel terkait mendahulukan kategori yang sama. |
| Terbit terjadwal | Artikel tampil bila status Terbit **dan** `published_at` ≤ sekarang, jadi waktu terbit di masa depan berlaku sebagai jadwal tanpa cron. |

## Ronde 4: revisi tampilan (2026-11-16 – 2026-11-17)

| Permintaan | Keputusan |
|------------|-----------|
| Laci filter katalog di HP tidak bisa ditutup | Tombol **×** di kepala laci + latar gelap (backdrop) yang menutup laci saat diketuk; tetap berfungsi setelah hasil katalog dimuat ulang lewat `fetch`. |
| Editor artikel aneh di mode gelap | TinyMCE dimuat ulang dengan skin & CSS isi gelap/terang saat tema berganti (event `themechange`), isi editor tidak hilang. |
| Sidebar mini tidak bisa di-scroll | Sidebar mode ikon tetap `overflow-y: auto`; label pil memakai posisi `fixed` mengikuti item yang disorot. |
| Latar "hidup" seperti live wallpaper | **Liquid gradient WebGL** (`public/js/live-bg.js`): shader domain-warping (fbm) ~30 fps di resolusi rendah, palet per tema dari CSS (`--live-c0..c4`) yang berpindah halus saat tema diganti. Lapisan tipis `--live-veil` menjaga kontras teks ≥ 4,5 (diukur di banyak frame). Tanpa WebGL: blob CSS beranimasi. Mode "Animasi: Hemat" = satu frame diam. Dipakai di website, panel, dan halaman login. |

## Data baru (ringkas)

| Tabel | Kolom penting | Relasi |
|-------|---------------|--------|
| `product_images` | `path`, `sort_order` | N → 1 `products` (hapus produk = hapus galeri) |
| `article_categories` | `name`, `slug`, `kanji`, `color`, `sort_order` | 1 → N `articles` |
| `articles` | `title`, `slug`, `excerpt`, `body` (HTML bersih), `cover`, `status`, `is_pinned`, `published_at`, `views` | N → 1 `article_categories`, N → 1 `users` (penulis) |

Diagram (peta halaman, use case, Activity 10 "Kelola & Baca Artikel", ERD, LRS) ada di `docs/diagrams/kz-hobby-store.drawio`.

## Di luar cakupan
Komentar artikel, SEO sitemap, notifikasi artikel baru.
