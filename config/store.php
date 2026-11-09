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
];
