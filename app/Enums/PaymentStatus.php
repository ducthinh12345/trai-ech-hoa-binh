<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Chưa thanh toán',
            self::Paid => 'Đã thanh toán',
            self::Failed => 'Thanh toán lỗi',
            self::Refunded => 'Đã hoàn tiền',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Refunded => 'warning',
        };
    }
}
