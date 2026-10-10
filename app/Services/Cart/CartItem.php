<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Một dòng trong giỏ, luôn được dựng lại từ DB để giá và tồn kho là số mới nhất.
 */
final class CartItem
{
    public function __construct(
        public readonly string $key,
        public readonly Product $product,
        public readonly ?ProductVariant $variant,
        public readonly int $quantity,
    ) {}

    public static function makeKey(int $productId, ?int $variantId): string
    {
        return $variantId ? "{$productId}:{$variantId}" : "{$productId}";
    }

    public function unitPrice(): int
    {
        return $this->variant?->price ?? $this->product->price;
    }

    public function lineTotal(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function name(): string
    {
        return $this->product->name;
    }

    public function variantName(): ?string
    {
        return $this->variant?->name;
    }

    public function sku(): ?string
    {
        return $this->variant?->sku ?? $this->product->sku;
    }

    public function imageUrl(): string
    {
        return $this->product->thumbnailUrl();
    }

    public function imagePath(): ?string
    {
        return ($this->product->images ?? [])[0] ?? null;
    }

    /**
     * Số lượng tối đa có thể mua; null = không giới hạn (không theo dõi tồn kho).
     */
    public function maxQuantity(): ?int
    {
        if (! $this->product->track_stock) {
            return null;
        }

        return $this->variant?->stock_quantity ?? $this->product->stock_quantity;
    }

    public function isAvailable(): bool
    {
        $max = $this->maxQuantity();

        return $this->product->is_active && ($max === null || $max >= $this->quantity);
    }
}
