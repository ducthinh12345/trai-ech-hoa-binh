<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\CartException;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\OrderStatusService;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentManager
{
    public function __construct(
        private readonly VnpayGateway $vnpay,
        private readonly MomoGateway $momo,
        private readonly VietQr $vietQr,
        private readonly OrderStatusService $orderStatus,
    ) {}

    /**
     * Phương thức khách được chọn lúc checkout: bật trong Cài đặt và (với cổng online) đã có khoá.
     *
     * @return array<int, PaymentMethod>
     */
    public function availableMethods(): array
    {
        $methods = [];

        if ((bool) setting('payment_cod_enabled', true)) {
            $methods[] = PaymentMethod::Cod;
        }
        if ((bool) setting('payment_bank_enabled', true) && $this->vietQr->isConfigured()) {
            $methods[] = PaymentMethod::BankTransfer;
        }
        if ((bool) setting('payment_vnpay_enabled', false) && $this->vnpay->isConfigured()) {
            $methods[] = PaymentMethod::Vnpay;
        }
        if ((bool) setting('payment_momo_enabled', false) && $this->momo->isConfigured()) {
            $methods[] = PaymentMethod::Momo;
        }

        return $methods;
    }

    public function isAvailable(PaymentMethod $method): bool
    {
        return in_array($method, $this->availableMethods(), true);
    }

    public function gateway(PaymentMethod $method): PaymentGateway
    {
        return match ($method) {
            PaymentMethod::Vnpay => $this->vnpay,
            PaymentMethod::Momo => $this->momo,
            default => throw new InvalidArgumentException("{$method->value} không phải cổng thanh toán online."),
        };
    }

    /**
     * Mỗi lượt bấm "thanh toán" tạo một transaction_ref mới (cổng yêu cầu mã duy nhất),
     * dạng {mã đơn}-{số lần}. Trả về URL để redirect khách.
     */
    public function initiate(Order $order, string $clientIp): string
    {
        if ($order->isPaid()) {
            throw new CartException('Đơn hàng này đã được thanh toán.');
        }
        if ($order->isCancelled()) {
            throw new CartException('Đơn hàng đã bị huỷ.');
        }

        $gateway = $this->gateway($order->payment_method);
        $attempt = $order->paymentTransactions()->where('gateway', $gateway->method()->value)->count() + 1;
        $ref = "{$order->code}-{$attempt}";

        $transaction = $order->paymentTransactions()->create([
            'gateway' => $gateway->method()->value,
            'transaction_ref' => $ref,
            'amount' => $order->total,
            'status' => PaymentTransaction::STATUS_INITIATED,
        ]);

        $created = $gateway->createPaymentUrl($order, $ref, $clientIp);
        $transaction->update(['request_payload' => $created['payload']]);

        return $created['url'];
    }

    /**
     * Xử lý return URL lẫn IPN cùng một chỗ, idempotent: callback thứ hai cho giao dịch đã ghi không đổi gì.
     * Kết quả có $result->order để controller điều hướng; order null = không tìm thấy giao dịch.
     */
    public function handleCallback(PaymentMethod $method, array $params): PaymentResult
    {
        $result = $this->gateway($method)->parseCallback($params);

        $transaction = PaymentTransaction::query()
            ->where('gateway', $method->value)
            ->where('transaction_ref', $result->transactionRef)
            ->with('order')
            ->first();

        if (! $transaction) {
            return $result;
        }

        $result->order = $transaction->order;

        if (! $result->signatureValid) {
            return $result;
        }

        DB::transaction(function () use ($transaction, $result): void {
            $order = $transaction->order()->lockForUpdate()->first();
            $amountMatches = $result->amount === $transaction->amount;

            if ($transaction->status === PaymentTransaction::STATUS_SUCCESS) {
                return;
            }

            $transaction->update([
                'status' => $result->success && $amountMatches ? PaymentTransaction::STATUS_SUCCESS : PaymentTransaction::STATUS_FAILED,
                'gateway_transaction_id' => $result->gatewayTransactionId,
                'response_code' => $result->responseCode,
                'message' => $amountMatches ? $result->message : 'Số tiền cổng trả về không khớp đơn hàng.',
                'response_payload' => $result->raw,
            ]);

            if ($result->success && $amountMatches && ! $order->isPaid()) {
                $this->markPaid($order, 'Thanh toán '.$result->order->payment_method->shortLabel().' thành công, mã GD '.$result->gatewayTransactionId);
            } elseif (! $result->success && ! $order->isPaid()) {
                $order->update(['payment_status' => PaymentStatus::Failed]);
            }
        });

        $result->order->refresh();

        return $result;
    }

    /**
     * Ghi nhận đã thu tiền (từ cổng online hoặc admin xác nhận chuyển khoản) và tự xác nhận đơn đang chờ.
     */
    public function markPaid(Order $order, ?string $note = null, ?int $userId = null): void
    {
        $order->forceFill([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ])->save();

        if ($order->status === OrderStatus::Pending) {
            $this->orderStatus->transition($order, OrderStatus::Confirmed, $note ?? 'Đã nhận thanh toán.', $userId);
        }
    }

    public function vietQr(): VietQr
    {
        return $this->vietQr;
    }
}
