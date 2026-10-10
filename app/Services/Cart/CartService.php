<?php

namespace App\Services\Cart;

use App\Exceptions\CartException;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Giỏ hàng lưu trong session dưới dạng [key => ['product_id', 'variant_id', 'quantity']].
 * Không lưu giá trong session: giá luôn lấy từ DB lúc hiển thị/đặt hàng để admin đổi giá là có hiệu lực ngay.
 */
class CartService
{
    private const SESSION_KEY = 'cart.items';

    private const COUPON_KEY = 'cart.coupon';

    /** @var Collection<string, CartItem>|null */
    private ?Collection $resolved = null;

    public function __construct(
        private readonly Session $session,
        private readonly CouponService $coupons,
    ) {}

    /**
     * @return Collection<string, CartItem>
     */
    public function items(): Collection
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $raw = $this->session->get(self::SESSION_KEY, []);

        if ($raw === []) {
            return $this->resolved = collect();
        }

        $products = Product::query()->with('variants')->whereIn('id', collect($raw)->pluck('product_id')->unique())->get()->keyBy('id');

        $items = collect();
        foreach ($raw as $key => $line) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                continue;
            }
            $variant = $line['variant_id'] ? $product->variants->firstWhere('id', $line['variant_id']) : null;
            if ($line['variant_id'] && ! $variant) {
                continue;
            }
            $items->put($key, new CartItem($key, $product, $variant, (int) $line['quantity']));
        }

        /* Dòng trỏ tới sản phẩm đã xoá được dọn luôn khỏi session. */
        if ($items->count() !== count($raw)) {
            $this->persist($items->map(fn (CartItem $item) => $this->toRaw($item))->all());
        }

        return $this->resolved = $items;
    }

    public function add(Product $product, ?ProductVariant $variant, int $quantity = 1): CartItem
    {
        if (! $product->is_active) {
            throw new CartException('Sản phẩm này hiện không còn bán.');
        }
        if ($variant && $variant->product_id !== $product->id) {
            throw new CartException('Quy cách không thuộc sản phẩm này.');
        }
        if ($product->hasVariants() && ! $variant) {
            throw new CartException('Vui lòng chọn quy cách trước khi thêm vào giỏ.');
        }

        $key = CartItem::makeKey($product->id, $variant?->id);
        $existing = $this->items()->get($key);
        $newQuantity = max($product->min_order_quantity, ($existing?->quantity ?? 0) + max(1, $quantity));

        $item = new CartItem($key, $product, $variant, $newQuantity);
        $this->assertQuantityAvailable($item);

        $this->resolved->put($key, $item);
        $this->persistResolved();

        return $item;
    }

    public function update(string $key, int $quantity): void
    {
        $existing = $this->items()->get($key);
        if (! $existing) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($key);

            return;
        }

        $quantity = max($existing->product->min_order_quantity, $quantity);
        $item = new CartItem($key, $existing->product, $existing->variant, $quantity);
        $this->assertQuantityAvailable($item);

        $this->resolved->put($key, $item);
        $this->persistResolved();
    }

    public function remove(string $key): void
    {
        $this->items()->forget($key);
        $this->persistResolved();
    }

    public function clear(): void
    {
        $this->resolved = collect();
        $this->session->forget([self::SESSION_KEY, self::COUPON_KEY]);
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function count(): int
    {
        return (int) $this->items()->sum(fn (CartItem $item) => $item->quantity);
    }

    public function subtotal(): int
    {
        return (int) $this->items()->sum(fn (CartItem $item) => $item->lineTotal());
    }

    public function applyCoupon(string $code, ?int $userId = null): Coupon
    {
        $coupon = $this->coupons->validate($code, $this->subtotal(), $userId);
        $this->session->put(self::COUPON_KEY, $coupon->code);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        $this->session->forget(self::COUPON_KEY);
    }

    /**
     * Mã đang gắn, đã kiểm lại điều kiện với giỏ hiện tại; tự gỡ nếu không còn hợp lệ.
     */
    public function coupon(?int $userId = null): ?Coupon
    {
        $code = $this->session->get(self::COUPON_KEY);
        if (! $code) {
            return null;
        }

        try {
            return $this->coupons->validate($code, $this->subtotal(), $userId);
        } catch (CartException) {
            $this->removeCoupon();

            return null;
        }
    }

    public function discount(?int $userId = null): int
    {
        return $this->coupon($userId)?->discountFor($this->subtotal()) ?? 0;
    }

    /**
     * Kiểm tra toàn giỏ trước khi đặt hàng; trả danh sách lỗi theo dòng.
     *
     * @return array<string, string>
     */
    public function problems(): array
    {
        $problems = [];
        foreach ($this->items() as $key => $item) {
            if (! $item->product->is_active) {
                $problems[$key] = "{$item->name()} đã ngừng bán.";
            } elseif (! $item->isAvailable()) {
                $problems[$key] = "{$item->name()} chỉ còn {$item->maxQuantity()} {$item->product->unit}.";
            }
        }

        return $problems;
    }

    private function assertQuantityAvailable(CartItem $item): void
    {
        $max = $item->maxQuantity();
        if ($max !== null && $item->quantity > $max) {
            throw new CartException($max > 0
                ? "Chỉ còn {$max} {$item->product->unit} {$item->name()} trong kho."
                : "{$item->name()} hiện đã hết hàng.");
        }
    }

    private function persistResolved(): void
    {
        $this->persist($this->items()->map(fn (CartItem $item) => $this->toRaw($item))->all());
    }

    /**
     * @param  array<string, array{product_id: int, variant_id: int|null, quantity: int}>  $raw
     */
    private function persist(array $raw): void
    {
        $this->session->put(self::SESSION_KEY, $raw);
    }

    /**
     * @return array{product_id: int, variant_id: int|null, quantity: int}
     */
    private function toRaw(CartItem $item): array
    {
        return ['product_id' => $item->product->id, 'variant_id' => $item->variant?->id, 'quantity' => $item->quantity];
    }
}
