<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Chuyển phát tiêu chuẩn',
            'fee' => 45000,
            'free_from_amount' => 1000000,
            'province_codes' => null,
            'is_active' => true,
        ];
    }
}
