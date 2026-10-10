<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\CartException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\Cart\CartItem;
use App\Services\Cart\CartService;
use App\Support\Provinces;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly ShippingCalculator $shipping,
    ) {}

    /**
     * Tạo đơn trong một transaction: khoá dòng tồn kho, trừ kho, ghi snapshot giá, tăng lượt dùng coupon.
     * Giỏ chỉ được xoá sau khi commit để lỗi giữa chừng không làm khách mất giỏ.
     */
    public function placeOrder(CartService $cart, CheckoutData $data): Order
    {
        if ($cart->isEmpty()) {
            throw new CartException('Giỏ hàng đang trống.');
        }
        if (! Provinces::exists($data->provinceCode)) {
            throw new CartException('Tỉnh/thành không hợp lệ.');
        }

        $method = $this->shipping->methodsFor($data->provinceCode)->firstWhere('id', $data->shippingMethodId);
        if (! $method instanceof ShippingMethod) {
            throw new CartException('Phương thức vận chuyển không áp dụng cho khu vực này.');
        }

        $order = DB::transaction(function () use ($cart, $data, $method): Order {
            $items = $cart->items();
            $this->reserveStock($items);

            $subtotal = $cart->subtotal();
            $coupon = $cart->coupon($data->userId);
            $discount = $coupon?->discountFor($subtotal) ?? 0;
            $shippingFee = $this->shipping->fee($method, $subtotal, $coupon);

            $order = Order::create([
                'user_id' => $data->userId,
                'customer_name' => $data->customerName,
                'customer_phone' => $data->customerPhone,
                'customer_email' => $data->customerEmail,
                'province_code' => $data->provinceCode,
                'province_name' => Provinces::name($data->provinceCode),
                'ward' => $data->ward,
                'street' => $data->street,
                'customer_note' => $data->note,
                'shipping_method_id' => $method->id,
                'shipping_method_name' => $method->name,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'coupon_code' => $coupon?->code,
                'shipping_fee' => $shippingFee,
                'total' => max(0, $subtotal - $discount + $shippingFee),
                'payment_method' => $data->paymentMethod,
                'payment_status' => PaymentStatus::Unpaid,
                'status' => OrderStatus::Pending,
                'ip_address' => $data->ipAddress,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item->product->id,
                    'product_variant_id' => $item->variant?->id,
                    'product_name' => $item->name(),
                    'variant_name' => $item->variantName(),
                    'sku' => $item->sku(),
                    'image' => $item->imagePath(),
                    'unit' => $item->product->unit,
                    'unit_price' => $item->unitPrice(),
                    'quantity' => $item->quantity,
                    'line_total' => $item->lineTotal(),
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'note' => 'Khách đặt hàng trên website.',
            ]);

            if ($coupon instanceof Coupon) {
                $coupon->increment('used_count');
            }

            return $order;
        });

        $cart->clear();

        OrderPlaced::dispatch($order);

        return $order->load('items');
    }

    /**
     * Khoá và trừ tồn kho từng dòng; ném lỗi nếu ai đó vừa mua hết trong lúc khách điền form.
     *
     * @param  Collection<string, CartItem>  $items
     */
    private function reserveStock($items): void
    {
        foreach ($items as $item) {
            $product = Product::query()->lockForUpdate()->find($item->product->id);
            if (! $product || ! $product->is_active) {
                throw new CartException("{$item->name()} đã ngừng bán, vui lòng bỏ khỏi giỏ.");
            }

            if (! $product->track_stock) {
                $product->increment('sold_count', $item->quantity);

                continue;
            }

            if ($item->variant) {
                $variant = ProductVariant::query()->lockForUpdate()->find($item->variant->id);
                if (! $variant || $variant->stock_quantity < $item->quantity) {
                    throw new CartException("{$item->name()} ({$item->variantName()}) chỉ còn ".($variant?->stock_quantity ?? 0)." {$product->unit}.");
                }
                $variant->decrement('stock_quantity', $item->quantity);
            } else {
                if ($product->stock_quantity < $item->quantity) {
                    throw new CartException("{$item->name()} chỉ còn {$product->stock_quantity} {$product->unit}.");
                }
                $product->decrement('stock_quantity', $item->quantity);
            }

            $product->increment('sold_count', $item->quantity);
        }
    }
}
