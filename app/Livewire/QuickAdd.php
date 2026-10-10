<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Nút "thêm nhanh" trên thẻ sản phẩm. Sản phẩm có nhiều quy cách thì dẫn sang trang chi tiết để chọn.
 */
class QuickAdd extends Component
{
    #[Locked]
    public int $productId;

    #[Locked]
    public bool $needsOptions = false;

    #[Locked]
    public bool $inStock = true;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->needsOptions = $product->variants->where('is_active', true)->count() > 1;
        $this->inStock = $product->isInStock();
    }

    public function add(CartService $cart): void
    {
        $product = Product::query()->with('variants')->findOrFail($this->productId);

        try {
            $cart->add($product, $product->defaultVariant(), 1);
        } catch (CartException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: "Đã thêm {$product->name} vào giỏ.");
    }

    public function render(): View
    {
        return view('livewire.quick-add', ['url' => route('products.show', Product::query()->whereKey($this->productId)->value('slug'))]);
    }
}
