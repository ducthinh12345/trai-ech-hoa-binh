<?php

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Exceptions\CartException;
use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * MoMo AIO v2 (/v2/gateway/api/create). Chữ ký HMAC-SHA256 trên chuỗi key=value theo thứ tự MoMo quy định
 * (không phải sort alphabet toàn bộ), nên thứ tự trong rawSignature là cố định.
 */
class MomoGateway implements PaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Momo;
    }

    public function isConfigured(): bool
    {
        return filled($this->partnerCode()) && filled($this->accessKey()) && filled($this->secretKey());
    }

    public function createPaymentUrl(Order $order, string $transactionRef, string $clientIp): array
    {
        $requestId = $transactionRef.'-'.Str::lower(Str::random(6));
        $orderInfo = 'Thanh toan don hang '.$order->code;
        $redirectUrl = route('payment.momo.return');
        $ipnUrl = route('payment.momo.ipn');
        $requestType = (string) config('payment.momo.request_type', 'captureWallet');
        $extraData = '';

        $rawSignature = 'accessKey='.$this->accessKey()
            .'&amount='.$order->total
            .'&extraData='.$extraData
            .'&ipnUrl='.$ipnUrl
            .'&orderId='.$transactionRef
            .'&orderInfo='.$orderInfo
            .'&partnerCode='.$this->partnerCode()
            .'&redirectUrl='.$redirectUrl
            .'&requestId='.$requestId
            .'&requestType='.$requestType;

        $payload = [
            'partnerCode' => $this->partnerCode(),
            'partnerName' => (string) setting('store_name', config('app.name')),
            'storeId' => 'WebStore',
            'requestId' => $requestId,
            'amount' => $order->total,
            'orderId' => $transactionRef,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'requestType' => $requestType,
            'autoCapture' => true,
            'extraData' => $extraData,
            'signature' => hash_hmac('sha256', $rawSignature, $this->secretKey()),
        ];

        $response = Http::timeout(15)->acceptJson()->post($this->endpoint(), $payload);
        $body = $response->json() ?? [];

        if (! $response->successful() || (int) ($body['resultCode'] ?? -1) !== 0 || empty($body['payUrl'])) {
            throw new CartException('Không kết nối được với MoMo: '.($body['message'] ?? 'lỗi không xác định').'. Vui lòng thử lại hoặc chọn cách thanh toán khác.');
        }

        return [
            'url' => (string) $body['payUrl'],
            'payload' => ['request' => array_diff_key($payload, ['signature' => true]), 'response' => $body],
        ];
    }

    public function parseCallback(array $params): PaymentResult
    {
        $ref = (string) ($params['orderId'] ?? '');

        if (! $this->verifySignature($params)) {
            return PaymentResult::invalidSignature($ref, $params);
        }

        $resultCode = (string) ($params['resultCode'] ?? '');
        $success = $resultCode === '0';

        return new PaymentResult(
            transactionRef: $ref,
            signatureValid: true,
            success: $success,
            amount: (int) ($params['amount'] ?? 0),
            gatewayTransactionId: isset($params['transId']) ? (string) $params['transId'] : null,
            responseCode: $resultCode,
            message: $success ? 'Thanh toán MoMo thành công.' : ('MoMo: '.($params['message'] ?? 'giao dịch không thành công')),
            raw: $params,
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function verifySignature(array $params): bool
    {
        $received = (string) ($params['signature'] ?? '');

        $raw = 'accessKey='.$this->accessKey()
            .'&amount='.($params['amount'] ?? '')
            .'&extraData='.($params['extraData'] ?? '')
            .'&message='.($params['message'] ?? '')
            .'&orderId='.($params['orderId'] ?? '')
            .'&orderInfo='.($params['orderInfo'] ?? '')
            .'&orderType='.($params['orderType'] ?? '')
            .'&partnerCode='.($params['partnerCode'] ?? '')
            .'&payType='.($params['payType'] ?? '')
            .'&requestId='.($params['requestId'] ?? '')
            .'&responseTime='.($params['responseTime'] ?? '')
            .'&resultCode='.($params['resultCode'] ?? '')
            .'&transId='.($params['transId'] ?? '');

        $expected = hash_hmac('sha256', $raw, $this->secretKey());

        return $received !== '' && hash_equals($expected, $received);
    }

    private function partnerCode(): ?string
    {
        return setting('momo_partner_code') ?: config('payment.momo.partner_code');
    }

    private function accessKey(): string
    {
        return (string) (setting('momo_access_key') ?: config('payment.momo.access_key'));
    }

    private function secretKey(): string
    {
        return (string) (setting('momo_secret_key') ?: config('payment.momo.secret_key'));
    }

    private function endpoint(): string
    {
        return (string) (setting('momo_endpoint') ?: config('payment.momo.endpoint'));
    }
}
