<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Khối chọn quy cách + số lượng ở trang chi tiết. Giá cập nhật ngay khi đổi quy cách.
 */
class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    public ?int $variantId = null;

    public int $quantity = 1;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->variantId = $product->defaultVariant()?->id;
        $this->quantity = max(1, $product->min_order_quantity);
    }

    public function increment(): void
    {
        $this->quantity++;
    }

    public function decrement(): void
    {
        $this->quantity = max($this->product()->min_order_quantity, $this->quantity - 1);
    }

    public function add(CartService $cart, bool $buyNow = false): void
    {
        $product = $this->product();
        $variant = $this->variantId ? $product->variants->firstWhere('id', $this->variantId) : null;

        try {
            $cart->add($product, $variant, max(1, $this->quantity));
        } catch (CartException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        if ($buyNow) {
            $this->redirectRoute('checkout');

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: "Đã thêm {$product->name} vào giỏ hàng.");
    }

    public function render(): View
    {
        $product = $this->product();
        $variant = $this->variantId ? $product->variants->firstWhere('id', $this->variantId) : null;

        return view('livewire.add-to-cart', [
            'product' => $product,
            'variant' => $variant,
            'price' => $variant?->price ?? $product->price,
            'compareAt' => $variant ? $variant->compare_at_price : $product->compare_at_price,
            'stock' => $product->track_stock ? ($variant?->stock_quantity ?? $product->stock_quantity) : null,
        ]);
    }

    private function product(): Product
    {
        return Product::query()->with(['variants' => fn ($q) => $q->where('is_active', true)])->findOrFail($this->productId);
    }
}
