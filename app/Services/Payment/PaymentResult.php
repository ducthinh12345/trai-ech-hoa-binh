<?php

namespace App\Services\Payment;

use App\Models\Order;

/**
 * Kết quả đã chuẩn hoá từ callback của mọi cổng, để controller/IPN xử lý một kiểu.
 */
final class PaymentResult
{
    public ?Order $order = null;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $transactionRef,
        public readonly bool $signatureValid,
        public readonly bool $success,
        public readonly int $amount,
        public readonly ?string $gatewayTransactionId,
        public readonly ?string $responseCode,
        public readonly string $message,
        public readonly array $raw,
    ) {}

    public static function invalidSignature(string $ref, array $raw): self
    {
        return new self($ref, false, false, 0, null, null, 'Chữ ký không hợp lệ.', $raw);
    }
}
