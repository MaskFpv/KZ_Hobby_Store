<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

/**
 * Sapaan ala Jepang di halaman Home, berdasarkan jam (WIB) dan role user.
 * Ubah/ tambah teks cukup di konstanta di bawah.
 */
class Greeting
{
    /** [jam mulai, jepang, romaji, arti] urut dari jam terkecil. */
    private const TIME_GREETINGS = [
        [0, 'お疲れ様です', 'Otsukaresama desu', 'Terima kasih sudah bekerja keras sampai larut'],
        [4, 'おはようございます', 'Ohayou gozaimasu', 'Selamat pagi'],
        [11, 'こんにちは', 'Konnichiwa', 'Selamat siang'],
        [15, 'こんにちは', 'Konnichiwa', 'Selamat sore'],
        [18, 'こんばんは', 'Konbanwa', 'Selamat malam'],
        [22, 'お疲れ様です', 'Otsukaresama desu', 'Terima kasih sudah bekerja keras sampai larut'],
    ];

    /** Gaya per role: honorifik, sebutan jabatan, pesan semangat. */
    private const ROLE_STYLES = [
        'super-admin' => [
            'honorific' => 'sama',
            'title_jp' => 'オーナー',
            'title' => 'Ōnā (Pemilik Toko)',
            'message' => 'Laporan penjualan, stok, dan antrean pre-order sudah siap untuk Anda periksa.',
        ],
        'admin' => [
            'honorific' => 'sama',
            'title_jp' => '店長',
            'title' => 'Tenchō (Manajer Toko)',
            'message' => 'Operasional toko hari ini siap Anda pantau dari dashboard.',
        ],
        'keuangan' => [
            'honorific' => 'san',
            'title_jp' => '経理',
            'title' => 'Keiri (Keuangan)',
            'message' => 'Omset, laba, dan pembayaran online menunggu dicek. Teliti ya!',
        ],
        'gudang' => [
            'honorific' => 'san',
            'title_jp' => '倉庫係',
            'title' => 'Sōko-gakari (Petugas Gudang)',
            'message' => 'Cek kiriman supplier dan stok yang menipis hari ini.',
        ],
        'cs' => [
            'honorific' => 'san',
            'title_jp' => '接客係',
            'title' => 'Sekkyaku-gakari (Layanan Pelanggan)',
            'message' => 'Pesanan online dan chat pelanggan menunggu balasanmu. 頑張って!',
        ],
        'kasir' => [
            'honorific' => 'san',
            'title_jp' => 'レジ係',
            'title' => 'Reji-gakari (Kasir)',
            'message' => 'Semangat melayani para kolektor hari ini! 頑張って! (Ganbatte!)',
        ],
    ];

    private const DEFAULT_STYLE = [
        'honorific' => 'san',
        'title_jp' => 'スタッフ',
        'title' => 'Sutaffu (Staf)',
        'message' => 'Selamat bekerja, semoga harimu menyenangkan!',
    ];

    /** Peribahasa Jepang, berganti setiap hari. [jepang, romaji, arti] */
    private const PROVERBS = [
        ['一期一会', 'Ichi-go ichi-e', 'Setiap pertemuan hanya terjadi sekali. Layani tiap pelanggan sebaik mungkin.'],
        ['七転び八起き', 'Nana korobi ya oki', 'Jatuh tujuh kali, bangkit delapan kali.'],
        ['継続は力なり', 'Keizoku wa chikara nari', 'Konsistensi adalah kekuatan.'],
        ['初心忘るべからず', 'Shoshin wasuru bekarazu', 'Jangan lupakan semangat saat pertama memulai.'],
        ['塵も積もれば山となる', 'Chiri mo tsumoreba yama to naru', 'Debu yang menumpuk pun bisa jadi gunung. Penjualan kecil tetap berarti.'],
        ['石の上にも三年', 'Ishi no ue ni mo sannen', 'Duduk di atas batu pun, tiga tahun akan menghangatkannya. Kesabaran membuahkan hasil.'],
        ['笑う門には福来る', 'Warau kado ni wa fuku kitaru', 'Keberuntungan datang ke rumah yang penuh senyum.'],
        ['急がば回れ', 'Isogaba maware', 'Kalau terburu-buru, ambil jalan memutar. Teliti itu lebih cepat.'],
    ];

    public static function for(User $user, ?Carbon $now = null): array
    {
        $now ??= now();
        $role = $user->getRoleNames()->first();
        $style = self::ROLE_STYLES[$role] ?? self::DEFAULT_STYLE;

        $time = self::TIME_GREETINGS[0];
        foreach (self::TIME_GREETINGS as $row) {
            if ($now->hour >= $row[0]) {
                $time = $row;
            }
        }

        $firstName = strtok(trim($user->name), ' ') ?: $user->name;
        $proverb = self::PROVERBS[$now->dayOfYear % count(self::PROVERBS)];

        return [
            'jp' => $time[1],
            'romaji' => $time[2],
            'meaning' => $time[3],
            'name' => "{$firstName}-{$style['honorific']}",
            'title_jp' => $style['title_jp'],
            'title' => $style['title'],
            'message' => $style['message'],
            'proverb' => ['jp' => $proverb[0], 'romaji' => $proverb[1], 'meaning' => $proverb[2]],
            // Melihat angka seluruh toko (bukan hanya transaksinya sendiri)
            'sees_all' => $user->can('view-reports'),
        ];
    }
}
