<?php

namespace App\Services\Payment\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\PaymentResult;

interface PaymentGateway
{
    public function method(): PaymentMethod;

    /**
     * Đủ khoá để gọi cổng chưa; thiếu thì không hiện phương thức này cho khách.
     */
    public function isConfigured(): bool;

    /**
     * Tạo URL để chuyển hướng khách sang cổng. $transactionRef là mã duy nhất cho lượt thanh toán này.
     *
     * @return array{url: string, payload: array<string, mixed>}
     */
    public function createPaymentUrl(Order $order, string $transactionRef, string $clientIp): array;

    /**
     * Đọc tham số return/IPN, kiểm chữ ký và trả kết quả chuẩn hoá. Không đụng DB.
     *
     * @param  array<string, mixed>  $params
     */
    public function parseCallback(array $params): PaymentResult;
}
