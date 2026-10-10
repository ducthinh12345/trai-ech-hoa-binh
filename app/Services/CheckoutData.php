<?php

namespace App\Services;

use App\Enums\PaymentMethod;

/**
 * Dữ liệu form checkout đã qua validate, tách khỏi Livewire để CheckoutService test được độc lập.
 */
final class CheckoutData
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $customerPhone,
        public readonly ?string $customerEmail,
        public readonly string $provinceCode,
        public readonly string $ward,
        public readonly string $street,
        public readonly ?string $note,
        public readonly int $shippingMethodId,
        public readonly PaymentMethod $paymentMethod,
        public readonly ?int $userId = null,
        public readonly ?string $ipAddress = null,
    ) {}
}
