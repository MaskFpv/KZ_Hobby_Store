<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: permission -> user -> master data -> produk.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
