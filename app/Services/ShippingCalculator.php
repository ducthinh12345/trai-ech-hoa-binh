<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * Phương thức áp dụng cho tỉnh đã chọn. Nếu có cấu hình riêng cho tỉnh thì chỉ dùng cấu hình riêng,
     * không trộn với mức toàn quốc (tránh khách thấy 2 giá cho cùng một vùng).
     *
     * @return Collection<int, ShippingMethod>
     */
    public function methodsFor(?string $provinceCode): Collection
    {
        $all = ShippingMethod::query()->active()->get();

        if (! $provinceCode) {
            return $all->filter(fn (ShippingMethod $m) => $m->isNationwide())->values();
        }

        $specific = $all->filter(fn (ShippingMethod $m) => ! $m->isNationwide() && $m->appliesToProvince($provinceCode));

        return ($specific->isNotEmpty() ? $specific : $all->filter(fn (ShippingMethod $m) => $m->isNationwide()))->values();
    }

    public function fee(ShippingMethod $method, int $subtotal, ?Coupon $coupon = null): int
    {
        if ($coupon?->type === CouponType::FreeShipping) {
            return 0;
        }

        return $method->feeFor($subtotal);
    }
}
