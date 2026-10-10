<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\Provinces;
use Illuminate\Database\Seeder;

/**
 * Đơn hàng giả lập 60 ngày gần nhất để dashboard có số liệu khi demo. Không trừ kho thật.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $products = Product::query()->with('variants')->get();
        $shipping = ShippingMethod::query()->where('name', 'like', 'Chuyển phát%')->first();
        $customers = User::factory()->count(12)->create();
        $provinces = Provinces::all();
        $names = ['Nguyễn Văn Hùng', 'Trần Thị Mai', 'Lê Văn Nam', 'Phạm Thị Lan', 'Hoàng Văn Tuấn', 'Vũ Thị Hằng', 'Đặng Văn Long', 'Bùi Thị Thu', 'Đỗ Văn Khánh', 'Ngô Thị Yến'];

        for ($i = 0; $i < 85; $i++) {
            $createdAt = now()->subDays(random_int(0, 59))->setTime(random_int(6, 21), random_int(0, 59));
            if ($createdAt->isFuture()) {
                $createdAt = now()->subMinutes(random_int(5, 300));
            }
            $status = $this->pickStatus($createdAt);
            $method = collect([PaymentMethod::Cod, PaymentMethod::Cod, PaymentMethod::BankTransfer, PaymentMethod::Vnpay, PaymentMethod::Momo])->random();
            $paid = $status === OrderStatus::Completed || ($method !== PaymentMethod::Cod && $status !== OrderStatus::Pending && $status !== OrderStatus::Cancelled);
            $province = $provinces->random();
            $customer = random_int(0, 2) ? $customers->random() : null;

            $lines = $products->random(random_int(1, 3))->map(function (Product $p) {
                $variant = $p->variants->isNotEmpty() ? $p->variants->random() : null;
                $qty = $variant ? random_int(1, 3) : random_int(1, 6);
                $price = $variant?->price ?? $p->price;

                return ['product' => $p, 'variant' => $variant, 'qty' => $qty, 'price' => $price];
            });
            $subtotal = $lines->sum(fn ($l) => $l['price'] * $l['qty']);
            $fee = $subtotal >= 1000000 ? 0 : 45000;

            $order = Order::create([
                'user_id' => $customer?->id,
                'customer_name' => $customer?->name ?? $names[array_rand($names)],
                'customer_phone' => $customer?->phone ?? '09'.random_int(10000000, 99999999),
                'customer_email' => $customer?->email,
                'province_code' => $province['code'],
                'province_name' => $province['name'],
                'ward' => 'Phường Trung Tâm',
                'street' => random_int(1, 200).' đường Chính',
                'shipping_method_id' => $shipping?->id,
                'shipping_method_name' => $shipping?->name,
                'subtotal' => $subtotal,
                'shipping_fee' => $fee,
                'total' => $subtotal + $fee,
                'payment_method' => $method,
                'payment_status' => $paid ? PaymentStatus::Paid : PaymentStatus::Unpaid,
                'status' => $status,
                'paid_at' => $paid ? $createdAt->copy()->addMinutes(10) : null,
                'confirmed_at' => $status !== OrderStatus::Pending ? $createdAt->copy()->addHour() : null,
                'completed_at' => $status === OrderStatus::Completed ? $createdAt->copy()->addDays(2) : null,
                'cancelled_at' => $status === OrderStatus::Cancelled ? $createdAt->copy()->addHours(3) : null,
                'cancel_reason' => $status === OrderStatus::Cancelled ? 'Khách đổi ý' : null,
            ]);
            $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

            foreach ($lines as $l) {
                $order->items()->create([
                    'product_id' => $l['product']->id,
                    'product_variant_id' => $l['variant']?->id,
                    'product_name' => $l['product']->name,
                    'variant_name' => $l['variant']?->name,
                    'sku' => $l['variant']?->sku ?? $l['product']->sku,
                    'image' => $l['product']->images[0] ?? null,
                    'unit' => $l['product']->unit,
                    'unit_price' => $l['price'],
                    'quantity' => $l['qty'],
                    'line_total' => $l['price'] * $l['qty'],
                ]);
            }

            $order->statusHistories()->create(['to_status' => OrderStatus::Pending, 'note' => 'Khách đặt hàng trên website.', 'created_at' => $createdAt]);
            if ($status !== OrderStatus::Pending) {
                $order->statusHistories()->create(['from_status' => OrderStatus::Pending, 'to_status' => $status, 'created_at' => $createdAt->copy()->addHours(2)]);
            }
        }

        $this->seedReviews($products);
    }

    private function pickStatus($createdAt): OrderStatus
    {
        if ($createdAt->gt(now()->subDay())) {
            return collect([OrderStatus::Pending, OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing])->random();
        }
        if ($createdAt->gt(now()->subDays(3))) {
            return collect([OrderStatus::Shipping, OrderStatus::Processing, OrderStatus::Completed])->random();
        }

        return random_int(1, 12) === 1 ? OrderStatus::Cancelled : OrderStatus::Completed;
    }

    private function seedReviews($products): void
    {
        $samples = [
            [5, 'Hàng giao nhanh, đóng gói cẩn thận. Ếch khoẻ, nhảy tanh tách khi mở thùng. Sẽ ủng hộ trại dài dài!'],
            [5, 'Chất lượng đúng như mô tả, giá tốt hơn ngoài chợ. Shop tư vấn nhiệt tình.'],
            [4, 'Sản phẩm tốt, giao hơi trễ một chút do mưa nhưng trại có gọi báo trước.'],
            [5, 'Lần thứ 3 đặt rồi, lần nào cũng ưng. Thịt ếch ngọt, không tanh.'],
            [4, 'Ổn áp, đóng gói đẹp. Mong trại có thêm nhiều combo tiết kiệm.'],
        ];
        $names = ['Anh Hải', 'Chị Ngọc', 'Anh Phúc', 'Cô Hạnh', 'Anh Quân', 'Chị Thảo'];

        foreach ($products as $product) {
            foreach (array_rand($samples, random_int(2, 4)) as $idx) {
                Review::create([
                    'product_id' => $product->id,
                    'reviewer_name' => $names[array_rand($names)],
                    'rating' => $samples[$idx][0],
                    'content' => $samples[$idx][1],
                    'is_approved' => random_int(0, 5) > 0,
                    'created_at' => now()->subDays(random_int(1, 50)),
                ]);
            }
        }
    }
}
