<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Return URL chỉ để điều hướng khách; IPN (server-to-server) là nguồn sự thật. Cả hai đều đi qua
 * PaymentManager::handleCallback nên idempotent, cổng nào gọi trước cũng được.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function vnpayReturn(Request $request): RedirectResponse
    {
        return $this->redirectAfter(PaymentMethod::Vnpay, $request);
    }

    /**
     * IPN VNPay: phải trả JSON {RspCode, Message} theo đặc tả, VNPay retry khi không nhận "00"/"02".
     */
    public function vnpayIpn(Request $request): JsonResponse
    {
        $result = $this->payments->handleCallback(PaymentMethod::Vnpay, $request->query());

        if (! $result->signatureValid) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }
        if (! $result->order) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }
        if ($result->amount !== $result->order->total) {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    public function momoReturn(Request $request): RedirectResponse
    {
        return $this->redirectAfter(PaymentMethod::Momo, $request);
    }

    /**
     * IPN MoMo: trả 204 để MoMo ngừng retry.
     */
    public function momoIpn(Request $request): Response
    {
        $result = $this->payments->handleCallback(PaymentMethod::Momo, $request->all());

        if (! $result->signatureValid) {
            Log::warning('MoMo IPN sai chữ ký', ['orderId' => $request->input('orderId')]);
        }

        return response()->noContent();
    }

    private function redirectAfter(PaymentMethod $method, Request $request): RedirectResponse
    {
        $result = $this->payments->handleCallback($method, $request->query());

        if (! $result->order) {
            return redirect()->route('home')->with('error', 'Không tìm thấy giao dịch thanh toán.');
        }

        OrderController::grantAccess($result->order);

        if (! $result->signatureValid) {
            return redirect()->route('orders.show', $result->order)->with('error', 'Không xác thực được kết quả thanh toán. Nếu đã bị trừ tiền, vui lòng liên hệ hotline.');
        }

        return redirect()->route('orders.show', $result->order)
            ->with($result->success ? 'success' : 'error', $result->message);
    }
}
