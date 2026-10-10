<?php

namespace App\Livewire;

use App\Services\Cart\CartService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void {}

    public function render(CartService $cart): View
    {
        return view('livewire.cart-counter', ['count' => $cart->count(), 'subtotal' => $cart->subtotal()]);
    }
}
