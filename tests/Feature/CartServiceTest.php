<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Exceptions\CartException;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    public function test_adding_same_product_twice_merges_quantity_and_uses_db_price(): void
    {
        $product = Product::factory()->create(['price' => 75000, 'stock_quantity' => 10]);

        $this->cart()->add($product, null, 2);
        $this->cart()->add($product, null, 3);

        $fresh = app(CartService::class);
        $this->assertSame(5, $fresh->count());
        $this->assertSame(375000, $fresh->subtotal());

        $product->update(['price' => 80000]);
        $this->assertSame(400000, app(CartService::class)->subtotal());
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3, 'unit' => 'kg']);

        $this->expectException(CartException::class);
        $this->expectExceptionMessage('Chỉ còn 3 kg');

        $this->cart()->add($product, null, 4);
    }

    public function test_untracked_stock_allows_any_quantity(): void
    {
        $product = Product::factory()->untracked()->create();

        $this->cart()->add($product, null, 500);

        $this->assertSame(500, $this->cart()->count());
    }

    public function test_product_with_variants_requires_variant_and_uses_variant_price(): void
    {
        $product = Product::factory()->create(['price' => 1800]);
        $variant = ProductVariant::factory()->for($product)->create(['price' => 180000, 'stock_quantity' => 5]);

        try {
            $this->cart()->add($product, null, 1);
            $this->fail('Phải yêu cầu chọn quy cách');
        } catch (CartException $e) {
            $this->assertStringContainsString('chọn quy cách', $e->getMessage());
        }

        $cart = app(CartService::class);
        $cart->add($product, $variant, 2);
        $this->assertSame(360000, $cart->subtotal());
    }

    public function test_min_order_quantity_is_enforced(): void
    {
        $product = Product::factory()->create(['min_order_quantity' => 3]);

        $this->cart()->add($product, null, 1);

        $this->assertSame(3, $this->cart()->count());
    }

    public function test_percent_coupon_respects_cap_and_minimum(): void
    {
        $product = Product::factory()->create(['price' => 500000, 'stock_quantity' => 10]);
        Coupon::factory()->create(['code' => 'GIAM10', 'type' => CouponType::Percent, 'value' => 10, 'max_discount_amount' => 80000, 'min_order_amount' => 600000]);

        $cart = $this->cart();
        $cart->add($product, null, 1);

        try {
            $cart->applyCoupon('giam10');
            $this->fail('Phải chặn đơn dưới mức tối thiểu');
        } catch (CartException $e) {
            $this->assertStringContainsString('tối thiểu', $e->getMessage());
        }

        $cart->update(array_key_first($cart->items()->all()), 2);
        $cart->applyCoupon('giam10');

        $this->assertSame(80000, $cart->discount());
    }

    public function test_coupon_is_dropped_when_cart_falls_below_minimum(): void
    {
        $product = Product::factory()->create(['price' => 400000, 'stock_quantity' => 10]);
        Coupon::factory()->create(['code' => 'FIXED', 'type' => CouponType::Fixed, 'value' => 50000, 'min_order_amount' => 700000]);

        $cart = $this->cart();
        $cart->add($product, null, 2);
        $cart->applyCoupon('FIXED');
        $this->assertSame(50000, $cart->discount());

        $cart->update(array_key_first($cart->items()->all()), 1);

        $this->assertSame(0, app(CartService::class)->discount());
        $this->assertNull(app(CartService::class)->coupon());
    }

    public function test_expired_and_exhausted_coupons_are_rejected(): void
    {
        Coupon::factory()->create(['code' => 'HETHAN', 'expires_at' => now()->subDay()]);
        Coupon::factory()->create(['code' => 'HETLUOT', 'usage_limit' => 5, 'used_count' => 5]);

        foreach (['HETHAN' => 'hết hạn', 'HETLUOT' => 'hết lượt'] as $code => $message) {
            try {
                $this->cart()->applyCoupon($code);
                $this->fail("Mã {$code} phải bị từ chối");
            } catch (CartException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }
}
