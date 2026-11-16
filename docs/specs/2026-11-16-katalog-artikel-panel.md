# Ronde 3: Katalog instan, Artikel, Galeri Produk, Panel "Sakura Ember"

## Masalah & keputusan

| # | Permintaan | Keputusan |
|---|------------|-----------|
| 1 | Filter katalog me-refresh halaman | Chip/harga/urut/cari/paginasi diambil lewat `fetch` (header `X-Catalog`), hasil ditukar tanpa reload + URL ikut berubah (history). Kartu baru muncul bertahap (GSAP). Tanpa JS tetap jalan sebagai link biasa. |
| 2 | Halaman artikel | Tabel `articles`, role baru **sosmed** (Admin Sosial Media, permission `articles`). Editor **TinyMCE 7** (lisensi GPL, CDN jsDelivr): heading, gambar (upload), tabel, embed video, kutipan. HTML dibersihkan **HTMLPurifier** sebelum disimpan. Website: `/artikel` (daftar + filter kategori instan) dan `/artikel/{slug}` (daftar isi otomatis, progres baca, artikel terkait). |
| 3 | Judul terpotong di tengah kata | Animasi split-text membungkus huruf per **kata** (`white-space: nowrap`). |
| 4 | Kartu katalog susah diklik (kursor teks) | Lapisan 3D (`translateZ`) menutupi link; link kartu dibawa ke depan, isi kartu `pointer-events: none`. |
| 5 | Halaman Pre-Order terlalu sederhana & kartu meleset | Spotlight PO (pilih kartu → tampil besar), kalkulator DP interaktif (jumlah & DP → sisa tagihan), filter instan (segera tutup / kategori), urutkan; hitung mundur tidak lagi melebarkan kartu. |
| 6 | Gambar produk lebih dari satu | Tabel `product_images` (galeri, urutan). Foto utama tetap `products.image`. Detail produk: galeri + thumbnail + perbesar. |
| 7 | Desain panel admin baru | Referensi "Ember Dashboard": sidebar melayang gelap (mode ikon), topbar kaca, kartu membulat besar, gradasi sakura-koral, animasi hover/masuk. Mode terang/gelap tetap. |

## Di luar cakupan
Komentar artikel, jadwal terbit otomatis (cron), SEO sitemap.
