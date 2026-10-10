<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\CartException;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Services\Cart\CartService;
use App\Services\CheckoutData;
use App\Services\CheckoutService;
use App\Services\OrderStatusService;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function data(ShippingMethod $method, PaymentMethod $payment = PaymentMethod::Cod, string $province = 'HN'): CheckoutData
    {
        return new CheckoutData('Nguyễn Văn A', '0912345678', 'a@example.com', $province, 'Phường Ba Đình', '12 Hoàng Hoa Thám', null, $method->id, $payment);
    }

    public function test_place_order_snapshots_prices_deducts_stock_and_clears_cart(): void
    {
        Event::fake([OrderPlaced::class]);
        $method = ShippingMethod::factory()->create(['fee' => 45000, 'free_from_amount' => 1000000]);
        $meat = Product::factory()->create(['name' => 'Ếch thịt', 'price' => 75000, 'stock_quantity' => 10]);
        $seed = Product::factory()->create(['name' => 'Ếch giống']);
        $bag = ProductVariant::factory()->for($seed)->create(['name' => 'Túi 100 con', 'price' => 180000, 'stock_quantity' => 4]);

        $cart = app(CartService::class);
        $cart->add($meat, null, 2);
        $cart->add($seed, $bag, 3);

        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($method));

        $this->assertSame(150000 + 540000, $order->subtotal);
        $this->assertSame(45000, $order->shipping_fee);
        $this->assertSame(735000, $order->total);
        $this->assertSame('Hà Nội', $order->province_name);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertMatchesRegularExpression('/^TE-\d{6}-[A-Z0-9]{4}$/', $order->code);
        $this->assertCount(2, $order->items);
        $this->assertSame('Túi 100 con', $order->items->firstWhere('product_id', $seed->id)->variant_name);

        $this->assertSame(8, $meat->fresh()->stock_quantity);
        $this->assertSame(1, $bag->fresh()->stock_quantity);
        $this->assertSame(2, $meat->fresh()->sold_count);
        $this->assertTrue(app(CartService::class)->isEmpty());
        $this->assertSame(1, $order->statusHistories()->count());
        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_free_shipping_threshold_and_coupon_usage_are_applied(): void
    {
        Event::fake([OrderPlaced::class]);
        $method = ShippingMethod::factory()->create(['fee' => 45000, 'free_from_amount' => 1000000]);
        $product = Product::factory()->create(['price' => 600000, 'stock_quantity' => 10]);
        $coupon = Coupon::factory()->create(['code' => 'GIAM50', 'type' => CouponType::Fixed, 'value' => 50000]);

        $cart = app(CartService::class);
        $cart->add($product, null, 2);
        $cart->applyCoupon('GIAM50');

        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($method));

        $this->assertSame(0, $order->shipping_fee);
        $this->assertSame(50000, $order->discount_amount);
        $this->assertSame(1150000, $order->total);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_free_shipping_coupon_zeroes_shipping_fee(): void
    {
        Event::fake([OrderPlaced::class]);
        $method = ShippingMethod::factory()->create(['fee' => 45000, 'free_from_amount' => null]);
        $product = Product::factory()->create(['price' => 100000]);
        Coupon::factory()->create(['code' => 'SHIP0', 'type' => CouponType::FreeShipping, 'value' => 0]);

        $cart = app(CartService::class);
        $cart->add($product, null, 1);
        $cart->applyCoupon('SHIP0');

        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($method));

        $this->assertSame(0, $order->shipping_fee);
        $this->assertSame(100000, $order->total);
    }

    public function test_province_specific_shipping_replaces_nationwide_option(): void
    {
        Event::fake([OrderPlaced::class]);
        $nationwide = ShippingMethod::factory()->create(['fee' => 45000]);
        $local = ShippingMethod::factory()->create(['name' => 'Nội tỉnh', 'fee' => 20000, 'province_codes' => ['PT']]);
        $product = Product::factory()->create(['price' => 100000]);

        $cart = app(CartService::class);
        $cart->add($product, null, 1);

        try {
            app(CheckoutService::class)->placeOrder($cart, $this->data($nationwide, province: 'PT'));
            $this->fail('Tỉnh có cấu hình riêng không được dùng mức toàn quốc');
        } catch (CartException) {
        }

        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($local, province: 'PT'));
        $this->assertSame(20000, $order->shipping_fee);
    }

    public function test_order_fails_and_keeps_cart_when_stock_ran_out_meanwhile(): void
    {
        $method = ShippingMethod::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $cart = app(CartService::class);
        $cart->add($product, null, 4);
        $product->update(['stock_quantity' => 2]);

        try {
            app(CheckoutService::class)->placeOrder($cart, $this->data($method));
            $this->fail('Phải báo hết hàng');
        } catch (CartException $e) {
            $this->assertStringContainsString('chỉ còn 2', $e->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertFalse(app(CartService::class)->isEmpty());
    }

    public function test_cancelling_order_restores_stock_and_coupon_usage(): void
    {
        Event::fake([OrderPlaced::class]);
        $method = ShippingMethod::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $coupon = Coupon::factory()->create(['code' => 'HUY', 'type' => CouponType::Fixed, 'value' => 1000]);

        $cart = app(CartService::class);
        $cart->add($product, null, 3);
        $cart->applyCoupon('HUY');
        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($method));
        $this->assertSame(7, $product->fresh()->stock_quantity);

        app(OrderStatusService::class)->transition($order, OrderStatus::Cancelled, 'Khách đổi ý');

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(0, $product->fresh()->sold_count);
        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertNotNull($order->fresh()->cancelled_at);
        $this->assertSame('Khách đổi ý', $order->fresh()->cancel_reason);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        Event::fake([OrderPlaced::class]);
        $method = ShippingMethod::factory()->create();
        $cart = app(CartService::class);
        $cart->add(Product::factory()->create(), null, 1);
        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($method));

        $this->expectException(\InvalidArgumentException::class);
        app(OrderStatusService::class)->transition($order, OrderStatus::Completed);
    }

    public function test_completing_cod_order_marks_it_paid(): void
    {
        Event::fake([OrderPlaced::class]);
        $method = ShippingMethod::factory()->create();
        $cart = app(CartService::class);
        $cart->add(Product::factory()->create(), null, 1);
        $order = app(CheckoutService::class)->placeOrder($cart, $this->data($method));
        $statuses = app(OrderStatusService::class);

        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipping, OrderStatus::Completed] as $to) {
            $statuses->transition($order, $to);
        }

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertNotNull($order->completed_at);
        $this->assertSame(5, $order->statusHistories()->count());
    }

    public function test_online_gateways_hidden_until_configured(): void
    {
        $this->assertNotContains(PaymentMethod::Vnpay, app(PaymentManager::class)->availableMethods());

        Setting::setMany(['payment_vnpay_enabled' => true, 'vnpay_tmn_code' => 'TEST', 'vnpay_hash_secret' => 'SECRET'], 'payment');

        $this->assertContains(PaymentMethod::Vnpay, app(PaymentManager::class)->availableMethods());
    }
}
