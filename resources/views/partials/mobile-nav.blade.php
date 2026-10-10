{{-- Thanh điều hướng đáy màn hình trên mobile: các thao tác hay dùng nhất trong tầm ngón cái. --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-900/10 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg lg:hidden" aria-label="Điều hướng nhanh">
    <div class="grid grid-cols-5 text-[10.5px] font-semibold">
        @php
            $items = config('app.static_demo') ? [
                ['route' => 'home', 'icon' => 'home', 'label' => 'Trang chủ', 'active' => request()->routeIs('home')],
                ['route' => 'products.index', 'icon' => 'squares-2x2', 'label' => 'Sản phẩm', 'active' => request()->routeIs('products.*', 'categories.*')],
                ['route' => 'posts.index', 'icon' => 'book-open', 'label' => 'Kiến thức', 'active' => request()->routeIs('posts.*')],
                ['route' => 'contact', 'icon' => 'chat-bubble-left-right', 'label' => 'Liên hệ', 'active' => request()->routeIs('contact')],
                ['route' => 'pages.show', 'params' => 'huong-dan-mua-hang', 'icon' => 'question-mark-circle', 'label' => 'Hướng dẫn', 'active' => request()->is('trang/huong-dan-mua-hang')],
            ] : [
                ['route' => 'home', 'icon' => 'home', 'label' => 'Trang chủ', 'active' => request()->routeIs('home')],
                ['route' => 'products.index', 'icon' => 'squares-2x2', 'label' => 'Sản phẩm', 'active' => request()->routeIs('products.*', 'categories.*')],
                ['route' => 'cart.index', 'icon' => 'shopping-bag', 'label' => 'Giỏ hàng', 'active' => request()->routeIs('cart.*', 'checkout')],
                ['route' => 'orders.track', 'icon' => 'truck', 'label' => 'Đơn hàng', 'active' => request()->routeIs('orders.*')],
                ['route' => auth()->check() ? 'account.index' : 'login', 'icon' => 'user', 'label' => 'Tài khoản', 'active' => request()->routeIs('account.*', 'login', 'register')],
            ];
        @endphp
        @foreach ($items as $item)
            <a href="{{ route($item['route'], $item['params'] ?? []) }}" @class(['flex flex-col items-center gap-1 py-2.5', 'text-brand-700' => $item['active'], 'text-gray-500' => ! $item['active']])>
                <x-dynamic-component :component="'heroicon-'.($item['active'] ? 's' : 'o').'-'.$item['icon']" class="size-6" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
