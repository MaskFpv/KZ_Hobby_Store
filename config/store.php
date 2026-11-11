<?php

/*
| Identitas toko. Dipakai di nota, PDF, sidebar, dan judul halaman.
| Ubah lewat .env tanpa menyentuh kode.
*/

return [
    'name' => env('STORE_NAME', 'KZ Hobby Store'),
    'tagline' => env('STORE_TAGLINE', 'Anime Figure, Gunpla & Merchandise'),
    'address' => env('STORE_ADDRESS', 'Jakarta, Indonesia'),
    'phone' => env('STORE_PHONE', '-'),

    // Website pelanggan
    'hours' => env('STORE_HOURS', 'Setiap hari 10.00 – 21.00 WIB'),
    'dp_min_percent' => (int) env('STORE_DP_MIN_PERCENT', 30),       // DP minimal pre-order online
    'payment_due_hours' => (int) env('STORE_PAYMENT_DUE_HOURS', 24), // batas bayar pesanan online
];
