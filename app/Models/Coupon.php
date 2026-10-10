<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'description', 'type', 'value', 'min_order_amount', 'max_discount_amount',
    'usage_limit', 'used_count', 'per_customer_limit', 'starts_at', 'expires_at', 'is_active',
])]
class Coupon extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount_amount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'per_customer_limit' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Coupon $coupon) => $coupon->code = strtoupper(trim($coupon->code)));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Còn hiệu lực về thời gian và lượt dùng; chưa xét điều kiện đơn hàng.
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Số tiền giảm trên tiền hàng (chưa gồm ship). FreeShipping trả 0 vì được xử lý ở phí ship.
     */
    public function discountFor(int $subtotal): int
    {
        return match ($this->type) {
            CouponType::Percent => min(
                (int) round($subtotal * min($this->value, 100) / 100),
                $this->max_discount_amount ?? PHP_INT_MAX
            ),
            CouponType::Fixed => min($this->value, $subtotal),
            CouponType::FreeShipping => 0,
        };
    }

    public function summary(): string
    {
        return match ($this->type) {
            CouponType::Percent => "Giảm {$this->value}%".($this->max_discount_amount ? ' (tối đa '.number_format($this->max_discount_amount, 0, ',', '.').'đ)' : ''),
            CouponType::Fixed => 'Giảm '.number_format($this->value, 0, ',', '.').'đ',
            CouponType::FreeShipping => 'Miễn phí vận chuyển',
        };
    }
}
