<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Contoh katalog. Format: [nama, kategori, brand, sku, modal, jual, stok, kondisi, tipe]
     */
    public function run(): void
    {
        $products = [
            ['Nendoroid Frieren', 'Action Figure', 'Good Smile Company', 'GSC-NEN-2367', 620000, 850000, 6, 'new', 'ready_stock'],
            ['figma Gojo Satoru', 'Action Figure', 'Max Factory', 'MXF-FIG-0612', 1100000, 1450000, 3, 'new', 'ready_stock'],
            ['ARTFX J Levi Ackerman', 'Action Figure', 'Kotobukiya', 'KTB-ARJ-0145', 1900000, 2350000, 1, 'second', 'ready_stock'],
            ['Grandista Monkey D. Luffy', 'Action Figure', 'Banpresto', 'BNP-GRD-0088', 350000, 495000, 12, 'new', 'ready_stock'],
            ['HG 1/144 Gundam Aerial', 'Gunpla', 'Bandai', 'BAN-HG-0230', 180000, 265000, 15, 'new', 'ready_stock'],
            ['RG 1/144 Nu Gundam', 'Gunpla', 'Bandai', 'BAN-RG-0032', 520000, 690000, 4, 'new', 'ready_stock'],
            ['MG 1/100 Strike Freedom 2.0', 'Gunpla', 'Bandai', 'BAN-MG-0255', 950000, 1250000, 0, 'new', 'pre_order'],
            ['Scale Figure Makima 1/7', 'Action Figure', 'Good Smile Company', 'GSC-SCL-0711', 2600000, 3200000, 0, 'new', 'pre_order'],
            ['Acrylic Stand Bocchi', 'Accessories', 'Good Smile Company', 'GSC-ACR-0051', 85000, 135000, 20, 'new', 'ready_stock'],
            ['T-Shirt Jujutsu Kaisen', 'Apparel / T-Shirt', 'Bandai', 'BAN-APP-0019', 95000, 159000, 18, 'new', 'ready_stock'],
        ];

        $categories = Category::pluck('id', 'name');
        $brands = Brand::pluck('id', 'name');

        foreach ($products as [$name, $category, $brand, $sku, $buy, $sell, $stock, $condition, $type]) {
            Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'category_id' => $categories[$category],
                'brand_id' => $brands[$brand],
                'purchase_price' => $buy,
                'selling_price' => $sell,
                'stock' => $stock,
                'condition' => $condition,
                'type' => $type,
            ]);
        }
    }
}
