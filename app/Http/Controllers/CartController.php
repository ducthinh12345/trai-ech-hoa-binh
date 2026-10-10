<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $seo = Seo::make('Giỏ hàng');
        $seo->noindex = true;

        return view('cart.index', compact('seo'));
    }

    public function checkout(): View
    {
        $seo = Seo::make('Thanh toán');
        $seo->noindex = true;

        return view('cart.checkout', compact('seo'));
    }
}
