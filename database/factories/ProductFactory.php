<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $buy = $this->faker->numberBetween(5, 300) * 10000;

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => ucfirst($this->faker->unique()->words(3, true)),
            'purchase_price' => $buy,
            'selling_price' => $buy * 1.3,
            'stock' => $this->faker->numberBetween(0, 20),
            'condition' => $this->faker->randomElement(array_keys(Product::CONDITIONS)),
            'type' => 'ready_stock',
        ];
    }

    public function preOrder(): static
    {
        return $this->state(fn () => ['type' => 'pre_order', 'stock' => 0]);
    }
}
