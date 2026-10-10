<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => 'Ếch '.fake()->unique()->word(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'short_description' => fake()->sentence(),
            'price' => 75000,
            'unit' => 'kg',
            'min_order_quantity' => 1,
            'track_stock' => true,
            'stock_quantity' => 50,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ];
    }

    public function untracked(): static
    {
        return $this->state(fn () => ['track_stock' => false, 'stock_quantity' => 0]);
    }
}
