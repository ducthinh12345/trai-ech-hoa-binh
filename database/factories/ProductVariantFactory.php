<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Túi '.fake()->numberBetween(1, 9).'00 con',
            'price' => 180000,
            'stock_quantity' => 10,
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
