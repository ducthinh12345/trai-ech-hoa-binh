<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Livewire\AddToCart;
use App\Livewire\CartPage;
use App\Livewire\CheckoutPage;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_to_cart_apply_coupon_and_place_cod_order(): void
    {
        Mail::fake();
        $this->seed(SettingSeeder::class);
        User::factory()->admin()->create();
        $method = ShippingMethod::factory()->create(['fee' => 45000, 'free_from_amount' => null]);
        $product = Product::factory()->create(['name' => 'Ếch giống Thái']);
        $variant = ProductVariant::factory()->for($product)->create(['name' => 'Túi 500 con', 'price' => 850000, 'stock_quantity' => 3, 'is_default' => true]);
        Coupon::factory()->create(['code' => 'GIAM10', 'value' => 10, 'max_discount_amount' => 50000]);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->assertSet('variantId', $variant->id)
            ->call('increment')
            ->call('add')
            ->assertDispatched('cart-updated');

        Livewire::test(CartPage::class)
            ->assertSee('Túi 500 con')
            ->set('couponCode', 'giam10')
            ->call('applyCoupon')
            ->assertHasNoErrors()
            ->assertSee('−50.000đ');

        Livewire::test(CheckoutPage::class)
            ->call('placeOrder')
            ->assertHasErrors(['customerName', 'customerPhone', 'provinceCode'])
            ->set('customerName', 'Trần Văn B')
            ->set('customerPhone', '0912 345 678')
            ->set('provinceCode', 'HN')
            ->set('ward', 'Phường Hoàn Kiếm')
            ->set('street', '1 Tràng Tiền')
            ->assertSet('shippingMethodId', $method->id)
            ->set('paymentMethod', PaymentMethod::Cod->value)
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame(1700000, $order->subtotal);
        $this->assertSame(50000, $order->discount_amount);
        $this->assertSame(45000, $order->shipping_fee);
        $this->assertSame(1695000, $order->total);
        $this->assertSame('0912345678', $order->customer_phone);
        $this->assertSame(1, $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('notifications', 1);

        $this->get(route('orders.show', $order))->assertOk()->assertSee('Đặt hàng thành công');
    }

    public function test_bank_transfer_order_shows_vietqr_with_exact_amount(): void
    {
        $this->seed(SettingSeeder::class);
        ShippingMethod::factory()->create(['fee' => 0]);
        $product = Product::factory()->create(['price' => 120000]);

        Livewire::test(AddToCart::class, ['product' => $product])->call('add');
        Livewire::test(CheckoutPage::class)
            ->set('customerName', 'A')->set('customerPhone', '0912345678')
            ->set('provinceCode', 'PT')->set('ward', 'X')->set('street', 'Y')
            ->set('paymentMethod', PaymentMethod::BankTransfer->value)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::sole();
        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('img.vietqr.io/image/970436-0123456789-compact2.png?amount=120000&amp;addInfo='.$order->code, false);
    }
}
