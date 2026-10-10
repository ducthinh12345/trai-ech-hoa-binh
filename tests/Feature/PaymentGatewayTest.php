<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\VnpayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const VNP_SECRET = 'TESTSECRETKEY1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setMany([
            'payment_vnpay_enabled' => true, 'vnpay_tmn_code' => 'TMN01', 'vnpay_hash_secret' => self::VNP_SECRET,
            'payment_momo_enabled' => true, 'momo_partner_code' => 'MOMOTEST', 'momo_access_key' => 'ACCESS', 'momo_secret_key' => 'MOMOSECRET',
        ], 'payment');
    }

    private function order(PaymentMethod $method, int $total = 735000): Order
    {
        return Order::create([
            'customer_name' => 'Test', 'customer_phone' => '0912345678', 'province_code' => 'HN', 'province_name' => 'Hà Nội',
            'ward' => 'X', 'street' => 'Y', 'subtotal' => $total, 'total' => $total, 'payment_method' => $method,
        ]);
    }

    /**
     * Ký giống hệt phía VNPay để giả lập return/IPN.
     */
    private function signVnpay(array $params): array
    {
        ksort($params);
        $query = implode('&', array_map(fn ($k, $v) => urlencode($k).'='.urlencode((string) $v), array_keys($params), $params));
        $params['vnp_SecureHash'] = hash_hmac('sha512', $query, self::VNP_SECRET);

        return $params;
    }

    public function test_vnpay_payment_url_is_signed_and_amount_multiplied_by_100(): void
    {
        $order = $this->order(PaymentMethod::Vnpay);

        $url = app(PaymentManager::class)->initiate($order, '127.0.0.1');
        parse_str(parse_url($url, PHP_URL_QUERY), $params);

        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/', $url);
        $this->assertSame('73500000', $params['vnp_Amount']);
        $this->assertSame($order->code.'-1', $params['vnp_TxnRef']);
        $this->assertTrue(app(VnpayGateway::class)->verifySignature($params));

        $params['vnp_Amount'] = '100';
        $this->assertFalse(app(VnpayGateway::class)->verifySignature($params));
    }

    public function test_vnpay_ipn_success_marks_order_paid_and_confirmed_once(): void
    {
        $order = $this->order(PaymentMethod::Vnpay);
        app(PaymentManager::class)->initiate($order, '127.0.0.1');

        $params = $this->signVnpay([
            'vnp_TmnCode' => 'TMN01', 'vnp_Amount' => 73500000, 'vnp_TxnRef' => $order->code.'-1',
            'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00', 'vnp_TransactionNo' => '14123456',
        ]);

        $this->getJson(route('payment.vnpay.ipn', $params))->assertExactJson(['RspCode' => '00', 'Message' => 'Confirm Success']);
        $this->getJson(route('payment.vnpay.ipn', $params))->assertExactJson(['RspCode' => '00', 'Message' => 'Confirm Success']);

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(2, $order->statusHistories()->count() + 1, 'Chỉ một lần chuyển sang Đã xác nhận');
        $this->assertSame('14123456', PaymentTransaction::first()->gateway_transaction_id);
    }

    public function test_vnpay_ipn_rejects_bad_signature_and_wrong_amount(): void
    {
        $order = $this->order(PaymentMethod::Vnpay);
        app(PaymentManager::class)->initiate($order, '127.0.0.1');

        $tampered = $this->signVnpay(['vnp_Amount' => 73500000, 'vnp_TxnRef' => $order->code.'-1', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00']);
        $tampered['vnp_Amount'] = 100;
        $this->getJson(route('payment.vnpay.ipn', $tampered))->assertJson(['RspCode' => '97']);

        $wrongAmount = $this->signVnpay(['vnp_Amount' => 100, 'vnp_TxnRef' => $order->code.'-1', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00']);
        $this->getJson(route('payment.vnpay.ipn', $wrongAmount))->assertJson(['RspCode' => '04']);

        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
    }

    public function test_vnpay_return_with_cancel_code_redirects_with_error(): void
    {
        $order = $this->order(PaymentMethod::Vnpay);
        app(PaymentManager::class)->initiate($order, '127.0.0.1');

        $params = $this->signVnpay(['vnp_Amount' => 73500000, 'vnp_TxnRef' => $order->code.'-1', 'vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']);

        $this->get(route('payment.vnpay.return', $params))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error', 'Bạn đã huỷ giao dịch.');

        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Thanh toán lại', false);
    }

    public function test_momo_create_and_ipn_flow(): void
    {
        Http::fake(['test-payment.momo.vn/*' => Http::response(['resultCode' => 0, 'payUrl' => 'https://test-payment.momo.vn/pay/abc'])]);
        $order = $this->order(PaymentMethod::Momo, 250000);

        $url = app(PaymentManager::class)->initiate($order, '127.0.0.1');
        $this->assertSame('https://test-payment.momo.vn/pay/abc', $url);

        Http::assertSent(function ($request) use ($order) {
            $raw = 'accessKey=ACCESS&amount=250000&extraData=&ipnUrl='.route('payment.momo.ipn').'&orderId='.$order->code.'-1&orderInfo=Thanh toan don hang '.$order->code
                .'&partnerCode=MOMOTEST&redirectUrl='.route('payment.momo.return').'&requestId='.$request['requestId'].'&requestType=captureWallet';

            return $request['signature'] === hash_hmac('sha256', $raw, 'MOMOSECRET') && $request['amount'] === 250000;
        });

        $ipn = ['partnerCode' => 'MOMOTEST', 'orderId' => $order->code.'-1', 'requestId' => 'r1', 'amount' => 250000, 'orderInfo' => 'x', 'orderType' => 'momo_wallet',
            'transId' => 998877, 'resultCode' => 0, 'message' => 'Successful.', 'payType' => 'qr', 'responseTime' => 1700000000000, 'extraData' => ''];
        $raw = 'accessKey=ACCESS&amount=250000&extraData=&message=Successful.&orderId='.$ipn['orderId'].'&orderInfo=x&orderType=momo_wallet&partnerCode=MOMOTEST&payType=qr&requestId=r1&responseTime=1700000000000&resultCode=0&transId=998877';
        $ipn['signature'] = hash_hmac('sha256', $raw, 'MOMOSECRET');

        $this->postJson(route('payment.momo.ipn'), $ipn)->assertNoContent();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }
}
