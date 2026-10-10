<?php

namespace App\Services\Payment;

use App\Models\Order;

/**
 * Mã QR chuyển khoản theo chuẩn VietQR qua dịch vụ ảnh img.vietqr.io: khách quét bằng app ngân hàng,
 * số tiền và nội dung (mã đơn) được điền sẵn để trại đối soát không nhầm.
 */
class VietQr
{
    public function isConfigured(): bool
    {
        return filled(setting('bank_bin')) && filled(setting('bank_account_number'));
    }

    public function imageUrl(Order $order): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $query = http_build_query([
            'amount' => $order->total,
            'addInfo' => $this->transferContent($order),
            'accountName' => (string) setting('bank_account_name'),
        ]);

        return sprintf('https://img.vietqr.io/image/%s-%s-compact2.png?%s', setting('bank_bin'), setting('bank_account_number'), $query);
    }

    public function transferContent(Order $order): string
    {
        return $order->code;
    }
}
