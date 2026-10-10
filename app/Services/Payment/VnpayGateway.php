<?php

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;

/**
 * VNPay Payment Gateway v2.1.0 (redirect). Chữ ký HMAC-SHA512 trên chuỗi query đã sort theo key.
 */
class VnpayGateway implements PaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Vnpay;
    }

    public function isConfigured(): bool
    {
        return filled($this->tmnCode()) && filled($this->hashSecret());
    }

    public function createPaymentUrl(Order $order, string $transactionRef, string $clientIp): array
    {
        $now = now('Asia/Ho_Chi_Minh');

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode(),
            'vnp_Amount' => $order->total * 100,
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $clientIp,
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang '.$order->code,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => route('payment.vnpay.return'),
            'vnp_TxnRef' => $transactionRef,
            'vnp_ExpireDate' => $now->copy()->addMinutes((int) config('payment.vnpay.expire_minutes', 15))->format('YmdHis'),
        ];

        $query = $this->buildQuery($params);
        $secureHash = hash_hmac('sha512', $query, $this->hashSecret());

        return [
            'url' => $this->payUrl().'?'.$query.'&vnp_SecureHash='.$secureHash,
            'payload' => $params,
        ];
    }

    public function parseCallback(array $params): PaymentResult
    {
        $ref = (string) ($params['vnp_TxnRef'] ?? '');

        if (! $this->verifySignature($params)) {
            return PaymentResult::invalidSignature($ref, $params);
        }

        $responseCode = (string) ($params['vnp_ResponseCode'] ?? '');
        $transactionStatus = (string) ($params['vnp_TransactionStatus'] ?? '');
        $success = $responseCode === '00' && $transactionStatus === '00';

        return new PaymentResult(
            transactionRef: $ref,
            signatureValid: true,
            success: $success,
            amount: (int) (((int) ($params['vnp_Amount'] ?? 0)) / 100),
            gatewayTransactionId: isset($params['vnp_TransactionNo']) ? (string) $params['vnp_TransactionNo'] : null,
            responseCode: $responseCode,
            message: $success ? 'Thanh toán VNPay thành công.' : $this->describeCode($responseCode),
            raw: $params,
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function verifySignature(array $params): bool
    {
        $received = (string) ($params['vnp_SecureHash'] ?? '');
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        $vnpParams = array_filter($params, fn ($key) => str_starts_with((string) $key, 'vnp_'), ARRAY_FILTER_USE_KEY);
        $expected = hash_hmac('sha512', $this->buildQuery($vnpParams), $this->hashSecret());

        return $received !== '' && hash_equals($expected, $received);
    }

    /**
     * Chuỗi dùng chung cho cả ký và query: sort key, urlencode từng vế — đúng cách VNPay quy định.
     *
     * @param  array<string, mixed>  $params
     */
    private function buildQuery(array $params): string
    {
        ksort($params);

        return implode('&', array_map(
            fn ($key, $value) => urlencode((string) $key).'='.urlencode((string) $value),
            array_keys($params),
            $params,
        ));
    }

    private function describeCode(string $code): string
    {
        return match ($code) {
            '07' => 'Giao dịch bị nghi ngờ gian lận, vui lòng liên hệ ngân hàng.',
            '09' => 'Thẻ/Tài khoản chưa đăng ký Internet Banking.',
            '10' => 'Xác thực thông tin thẻ sai quá 3 lần.',
            '11' => 'Hết thời gian chờ thanh toán.',
            '12' => 'Thẻ/Tài khoản bị khoá.',
            '13' => 'Sai mật khẩu xác thực giao dịch (OTP).',
            '24' => 'Bạn đã huỷ giao dịch.',
            '51' => 'Tài khoản không đủ số dư.',
            '65' => 'Tài khoản vượt hạn mức giao dịch trong ngày.',
            '75' => 'Ngân hàng thanh toán đang bảo trì.',
            '79' => 'Sai mật khẩu thanh toán quá số lần quy định.',
            default => 'Thanh toán không thành công (mã '.$code.').',
        };
    }

    private function tmnCode(): ?string
    {
        return setting('vnpay_tmn_code') ?: config('payment.vnpay.tmn_code');
    }

    private function hashSecret(): string
    {
        return (string) (setting('vnpay_hash_secret') ?: config('payment.vnpay.hash_secret'));
    }

    private function payUrl(): string
    {
        return (string) (setting('vnpay_url') ?: config('payment.vnpay.url'));
    }
}
