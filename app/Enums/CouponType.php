<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CouponType: string implements HasLabel
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeShipping = 'free_shipping';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percent => 'Giảm theo % đơn hàng',
            self::Fixed => 'Giảm số tiền cố định',
            self::FreeShipping => 'Miễn phí vận chuyển',
        };
    }
}
