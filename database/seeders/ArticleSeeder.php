<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Artikel contoh untuk halaman Artikel website. Aman dijalankan ulang (berdasarkan judul). */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'sosmed@gmail.com')->value('id');

        $articles = [
            [
                'title' => 'Panduan Pre-Order di KZ Hobby Store: dari DP sampai Barang di Tangan',
                'category' => 'toko',
                'excerpt' => 'Belum pernah ikut PO? Ini alur lengkapnya: pilih produk, bayar DP, pantau status, lalu ambil di toko.',
                'days' => 6,
                'body' => <<<'HTML'
<h2>Kenapa harus pre-order?</h2>
<p>Banyak figure dan gunpla diproduksi terbatas. Dengan <strong>pre-order</strong>, unitmu dikunci sejak pengumuman sehingga tidak perlu berebut saat rilis.</p>
<h2>Alur PO di website</h2>
<ol>
<li>Masuk sebagai member, buka menu <strong>Pre-Order</strong>.</li>
<li>Pilih produk, atur jumlah, dan tentukan DP (minimal 30%).</li>
<li>Bayar DP lewat QRIS, Virtual Account, atau kartu.</li>
<li>Pantau status di <em>Akun Saya</em>. Sisa tagihan boleh dicicil.</li>
<li>Saat status <strong>Barang Tiba</strong>, datang ke toko dan lunasi sisanya.</li>
</ol>
<blockquote>Batas pembayaran DP 24 jam. Kalau lewat, PO dibatalkan otomatis supaya slot bisa dipakai kolektor lain.</blockquote>
<h2>Tips supaya PO aman</h2>
<ul>
<li>Perhatikan hitung mundur batas PO di halaman produk.</li>
<li>Simpan kode PO, tunjukkan di kasir saat mengambil barang.</li>
<li>Ada pertanyaan? Pakai tombol <strong>Tanya CS</strong> di halaman PO.</li>
</ul>
HTML,
            ],
            [
                'title' => '5 Cara Merawat Action Figure agar Tetap Kinclong Bertahun-tahun',
                'category' => 'hobi',
                'excerpt' => 'Debu, sinar matahari, dan kelembapan adalah musuh utama koleksi. Begini cara melindunginya.',
                'days' => 3,
                'body' => <<<'HTML'
<h2>1. Jauhkan dari sinar matahari langsung</h2>
<p>Sinar UV membuat cat memudar dan plastik menguning. Letakkan lemari koleksi di sisi ruangan yang teduh.</p>
<h2>2. Bersihkan debu dengan kuas lembut</h2>
<p>Gunakan kuas makeup atau blower kamera seminggu sekali. Hindari kain kasar yang bisa menggores cat.</p>
<h2>3. Atur kelembapan</h2>
<p>Simpan <em>silica gel</em> di dalam etalase, terutama di musim hujan, untuk mencegah jamur pada box.</p>
<h2>4. Simpan box-nya</h2>
<p>Box dalam kondisi baik (<strong>MIB</strong>) menjaga nilai jual kembali figure-mu.</p>
<h2>5. Hati-hati dengan sendi PVC</h2>
<p>Jangan memaksa pose. Hangatkan sendi yang kaku dengan hair dryer jarak jauh sebelum digerakkan.</p>
HTML,
            ],
            [
                'title' => 'Rilisan Figure Anime yang Paling Ditunggu Musim Ini',
                'category' => 'anime',
                'excerpt' => 'Dari Frieren sampai Gundam terbaru: daftar rilisan yang sudah bisa kamu amankan lewat PO.',
                'days' => 1,
                'body' => <<<'HTML'
<p>Musim anime kali ini ramai dengan judul-judul besar, dan para produsen figure tidak mau ketinggalan.</p>
<h2>Highlight figure</h2>
<table><thead><tr><th>Produk</th><th>Brand</th><th>Status</th></tr></thead>
<tbody><tr><td>Scale Figure Makima 1/7</td><td>Good Smile Company</td><td>PO dibuka</td></tr>
<tr><td>MG 1/100 Strike Freedom 2.0</td><td>Bandai</td><td>PO dibuka</td></tr>
<tr><td>Nendoroid Frieren</td><td>Good Smile Company</td><td>Ready stock</td></tr></tbody></table>
<h2>Yang perlu diperhatikan</h2>
<p>Estimasi rilis bisa bergeser mengikuti jadwal pabrik. Kami selalu mengabarkan perubahan lewat chat member.</p>
<blockquote>Pantau terus menu <strong>Artikel</strong> untuk kabar rilisan berikutnya!</blockquote>
HTML,
            ],
        ];

        $articles[] = [
            'title' => 'Review RG 1/144 Nu Gundam: Rangka Dalam yang Bikin Nagih',
            'category' => 'review',
            'excerpt' => 'Detail inner frame, artikulasi, dan tips merakitnya dalam satu akhir pekan.',
            'days' => 2,
            'body' => <<<'HTML'
<p>RG Nu Gundam masih jadi salah satu kit Real Grade paling seru dirakit. Begini kesan kami setelah merakitnya.</p>
<h2>Kelebihan</h2>
<ul><li>Inner frame penuh, artikulasi luas.</li><li>Fin funnel bisa dipasang dengan mantap.</li><li>Warna sudah dekat dengan versi anime tanpa cat.</li></ul>
<h2>Kekurangan</h2>
<p>Beberapa bagian bahu mudah lepas saat dipose. Gunakan sedikit lem plastik bila perlu.</p>
<blockquote>Skor kami: 9/10. Wajib untuk penggemar Char's Counterattack!</blockquote>
HTML,
        ];
        $articles[] = [
            'title' => 'Tips Memotong Runner Gunpla agar Bebas Bekas Putih',
            'category' => 'tips',
            'excerpt' => 'Teknik dua kali potong, amplas bertingkat, dan alat yang wajib dimiliki pemula.',
            'days' => 4,
            'body' => <<<'HTML'
<h2>Alat yang dibutuhkan</h2>
<ol><li>Nipper satu mata (single blade).</li><li>Pisau hobi.</li><li>Amplas 400, 800, dan 1000.</li></ol>
<h2>Teknik dua kali potong</h2>
<p>Potong pertama agak jauh dari part, lalu potong kedua tepat di gate. Bekas putih (<em>stress mark</em>) jadi jauh berkurang.</p>
<h2>Rapikan dengan amplas</h2>
<p>Mulai dari grit kasar ke halus, lalu poles dengan kain microfiber.</p>
HTML,
        ];

        $categories = ArticleCategory::pluck('id', 'slug');
        foreach ($articles as $a) {
            Article::firstOrCreate(['title' => $a['title']], [
                'article_category_id' => $categories[$a['category']] ?? null,
                'excerpt' => $a['excerpt'],
                'body' => $a['body'],
                'status' => 'published',
                'published_at' => now()->subDays($a['days']),
                'user_id' => $author,
            ]);
        }
    }
}
