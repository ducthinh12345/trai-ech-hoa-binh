<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('MA????')),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_order_amount' => 0,
            'is_active' => true,
        ];
    }
}
