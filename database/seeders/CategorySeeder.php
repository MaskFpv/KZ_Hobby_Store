<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Action Figure', 'description' => 'Figure PVC, scale figure, Nendoroid, figma.'],
            ['name' => 'Gunpla', 'description' => 'Gundam plastic model kit (HG, RG, MG, PG).'],
            ['name' => 'Manga / Light Novel', 'description' => 'Komik dan novel ringan, edisi lokal maupun impor.'],
            ['name' => 'Apparel / T-Shirt', 'description' => 'Kaos, hoodie, jaket bertema anime.'],
            ['name' => 'Accessories', 'description' => 'Keychain, acrylic stand, poster, badge.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
