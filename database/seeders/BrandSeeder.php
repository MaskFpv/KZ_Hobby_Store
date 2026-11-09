<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Bandai', 'country' => 'Jepang'],
            ['name' => 'Good Smile Company', 'country' => 'Jepang'],
            ['name' => 'Kotobukiya', 'country' => 'Jepang'],
            ['name' => 'Max Factory', 'country' => 'Jepang'],
            ['name' => 'Banpresto', 'country' => 'Jepang'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand['name']], $brand);
        }
    }
}
