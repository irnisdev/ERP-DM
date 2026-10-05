<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(2, 10000, 500000);

        return [
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'barcode' => $this->faker->unique()->numerify('899#########'),
            'category_id' => ProductCategory::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'purchase_price' => $purchasePrice,
            'default_sell_price' => round($purchasePrice * 1.4, 2),
            'minimum_stock' => 5,
            'maximum_stock' => 200,
            'weight' => $this->faker->randomFloat(2, 0.1, 5),
            'status' => 'ACTIVE',
        ];
    }
}