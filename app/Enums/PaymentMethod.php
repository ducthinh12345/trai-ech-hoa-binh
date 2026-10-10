<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Phương thức thanh toán khách chọn ở bước checkout. Gateway online (VNPay, MoMo)
 * chỉ hiện khi đã cấu hình đủ khoá trong Cài đặt.
 */
enum PaymentMethod: string implements HasLabel
{
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
    case Vnpay = 'vnpay';
    case Momo = 'momo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cod => 'Thanh toán khi nhận hàng (COD)',
            self::BankTransfer => 'Chuyển khoản ngân hàng (quét mã VietQR)',
            self::Vnpay => 'Thanh toán qua VNPay (ATM, QR, thẻ quốc tế)',
            self::Momo => 'Ví MoMo',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Cod => 'COD',
            self::BankTransfer => 'Chuyển khoản',
            self::Vnpay => 'VNPay',
            self::Momo => 'MoMo',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cod => 'Kiểm tra hàng rồi mới trả tiền cho người giao.',
            self::BankTransfer => 'Quét mã QR bằng app ngân hàng, đơn được xác nhận sau khi trại nhận tiền.',
            self::Vnpay => 'Cổng thanh toán VNPay bảo mật, hỗ trợ hơn 40 ngân hàng.',
            self::Momo => 'Thanh toán nhanh bằng ví MoMo trên điện thoại.',
        };
    }

    /**
     * Gateway cần chuyển hướng ra ngoài để thanh toán.
     */
    public function isOnlineGateway(): bool
    {
        return in_array($this, [self::Vnpay, self::Momo], true);
    }
}
