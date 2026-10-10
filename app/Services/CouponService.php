<?php

namespace App\Services;

use App\Exceptions\CartException;
use App\Models\Coupon;
use App\Models\Order;

class CouponService
{
    /**
     * Kiểm tra mã theo thứ tự lỗi dễ hiểu nhất cho khách: tồn tại → hiệu lực → giá trị đơn → giới hạn mỗi khách.
     */
    public function validate(string $code, int $subtotal, ?int $userId = null): Coupon
    {
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->is_active) {
            throw new CartException('Mã giảm giá không tồn tại.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new CartException('Mã giảm giá chưa đến thời gian áp dụng.');
        }
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new CartException('Mã giảm giá đã hết hạn.');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new CartException('Mã giảm giá đã hết lượt sử dụng.');
        }
        if ($subtotal < $coupon->min_order_amount) {
            throw new CartException('Đơn hàng cần tối thiểu '.money($coupon->min_order_amount).' để dùng mã này.');
        }
        if ($coupon->per_customer_limit !== null && $userId !== null) {
            $used = Order::query()->where('user_id', $userId)->where('coupon_code', $coupon->code)
                ->where('status', '!=', 'cancelled')->count();
            if ($used >= $coupon->per_customer_limit) {
                throw new CartException('Bạn đã dùng hết số lần cho mã này.');
            }
        }

        return $coupon;
    }
}
