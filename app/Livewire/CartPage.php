<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\View\View;
use Livewire\Component;

class CartPage extends Component
{
    public string $couponCode = '';

    public function updateQuantity(CartService $cart, string $key, int $quantity): void
    {
        try {
            $cart->update($key, $quantity);
        } catch (CartException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
        $this->dispatch('cart-updated');
    }

    public function remove(CartService $cart, string $key): void
    {
        $cart->remove($key);
        $this->dispatch('cart-updated');
    }

    public function applyCoupon(CartService $cart): void
    {
        $this->resetErrorBag('couponCode');

        if (trim($this->couponCode) === '') {
            $this->addError('couponCode', 'Vui lòng nhập mã giảm giá.');

            return;
        }

        try {
            $coupon = $cart->applyCoupon($this->couponCode, auth()->id());
            $this->couponCode = '';
            $this->dispatch('toast', type: 'success', message: "Đã áp dụng mã {$coupon->code}: {$coupon->summary()}");
        } catch (CartException $e) {
            $this->addError('couponCode', $e->getMessage());
        }
    }

    public function removeCoupon(CartService $cart): void
    {
        $cart->removeCoupon();
    }

    public function render(CartService $cart): View
    {
        $items = $cart->items();
        $suggestions = $items->isEmpty()
            ? Product::query()->published()->featured()->with('variants')->limit(4)->get()
            : collect();

        return view('livewire.cart-page', [
            'items' => $items,
            'subtotal' => $cart->subtotal(),
            'coupon' => $cart->coupon(auth()->id()),
            'discount' => $cart->discount(auth()->id()),
            'problems' => $cart->problems(),
            'freeShipFrom' => (int) setting('free_shipping_threshold', 0),
            'suggestions' => $suggestions,
        ]);
    }
}
