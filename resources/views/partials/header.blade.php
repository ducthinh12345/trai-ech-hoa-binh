@php
    /* Cache dạng mảng thuần: cấu hình cache chặn unserialize object nên không cache model trực tiếp. */
    $menuCategories = collect(\Illuminate\Support\Facades\Cache::remember('menu.categories', 600, fn () => \App\Models\Category::query()->inMenu()->root()
        ->with(['children' => fn ($q) => $q->inMenu()])->get()
        ->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug, 'url' => $c->url(), 'children' => $c->children->map(fn ($ch) => ['name' => $ch->name, 'slug' => $ch->slug, 'url' => $ch->url()])->all()])
        ->all()))->map(fn ($c) => (object) [
            'name' => $c['name'], 'slug' => $c['slug'], 'url' => $c['url'],
            'children' => collect($c['children'])->map(fn ($ch) => (object) $ch),
            /* Mục cha sáng khi đang ở chính nó hoặc ở một danh mục con. */
            'isActive' => collect([$c['slug'], ...array_column($c['children'], 'slug')])->contains(fn ($slug) => request()->is('danh-muc/'.$slug)),
        ]);
    $phone = setting('store_phone', '0900000000');
@endphp
<header x-data="{ mobileOpen: false, searchOpen: false }" x-effect="document.documentElement.classList.toggle('overflow-hidden', mobileOpen)" class="sticky top-0 z-40">
    @if ($announcement = setting('announcement_text'))
        <div class="bg-brand-900 text-center text-xs font-medium text-brand-50 sm:text-[13px]">
            <div class="container-x flex items-center justify-center gap-2 py-2">
                <span class="hidden size-1.5 animate-pulse rounded-full bg-accent-400 sm:inline-block"></span>
                <span>{{ $announcement }}</span>
            </div>
        </div>
    @endif

    <div class="border-b border-brand-900/5 bg-white/90 backdrop-blur-lg">
        <div class="container-x flex h-16 items-center gap-3 lg:h-20 lg:gap-8">
            <button type="button" @click="mobileOpen = true" class="-ml-2 grid size-10 place-items-center rounded-full text-brand-900 hover:bg-brand-50 lg:hidden" aria-label="Mở menu">
                <x-heroicon-o-bars-3 class="size-6" />
            </button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ setting('store_name', config('app.name')) }} – Trang chủ">
                @if (setting('logo'))
                    <img src="{{ asset('storage/'.setting('logo')) }}" alt="{{ setting('store_name') }}" class="h-10 w-auto lg:h-12" width="160" height="48">
                @else
                    <img src="{{ asset('images/logo.svg') }}" alt="" class="size-10 lg:size-11" width="44" height="44">
                    <span class="leading-tight">
                        <span class="block text-base font-extrabold tracking-tight text-brand-900 lg:text-lg">{{ setting('store_name', config('app.name')) }}</span>
                        <span class="hidden text-[11px] font-medium text-brand-600 sm:block">{{ setting('store_tagline', 'Ếch sạch từ trại đến bàn ăn') }}</span>
                    </span>
                @endif
            </a>

            <form action="{{ route('products.index') }}" method="GET" role="search" class="relative hidden flex-1 md:block">
                <label for="header-search" class="sr-only">Tìm sản phẩm</label>
                <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="Tìm ếch giống, thịt ếch, ếch sống..."
                       class="input rounded-full bg-brand-50/60 py-2.5 pr-12 pl-5 ring-brand-900/5">
                <button class="absolute top-1/2 right-1.5 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-brand-700 text-white hover:bg-brand-800" aria-label="Tìm kiếm">
                    <x-heroicon-o-magnifying-glass class="size-4.5" />
                </button>
            </form>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="hidden items-center gap-2.5 rounded-full py-1.5 pr-4 pl-1.5 hover:bg-brand-50 xl:flex">
                    <span class="grid size-9 place-items-center rounded-full bg-accent-100 text-accent-700"><x-heroicon-s-phone class="size-4" /></span>
                    <span class="leading-tight">
                        <span class="block text-[11px] text-gray-500">Hotline đặt hàng</span>
                        <span class="block text-sm font-bold text-brand-900">{{ $phone }}</span>
                    </span>
                </a>
                <button type="button" @click="searchOpen = !searchOpen" class="grid size-10 place-items-center rounded-full text-brand-900 hover:bg-brand-50 md:hidden" aria-label="Tìm kiếm">
                    <x-heroicon-o-magnifying-glass class="size-6" />
                </button>
                @auth
                    <a href="{{ route('account.index') }}" class="hidden size-10 place-items-center rounded-full text-brand-900 hover:bg-brand-50 sm:grid" aria-label="Tài khoản">
                        <span class="grid size-8 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden size-10 place-items-center rounded-full text-brand-900 hover:bg-brand-50 sm:grid" aria-label="Đăng nhập">
                        <x-heroicon-o-user-circle class="size-6" />
                    </a>
                @endauth
                <livewire:cart-counter />
            </div>
        </div>

        <div x-show="searchOpen" x-collapse x-cloak class="border-t border-brand-900/5 md:hidden">
            <form action="{{ route('products.index') }}" method="GET" role="search" class="container-x py-3">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Bạn cần tìm gì?" class="input rounded-full" x-ref="mobileSearch">
            </form>
        </div>

        <nav class="hidden border-t border-brand-900/5 lg:block" aria-label="Danh mục chính">
            <div class="container-x flex items-center gap-0.5 text-sm font-semibold whitespace-nowrap">
                <a href="{{ route('products.index') }}" class="flex items-center gap-2 rounded-t-lg bg-brand-700 px-4 py-3 text-white">
                    <x-heroicon-o-squares-2x2 class="size-4.5" /> Sản phẩm
                </a>
                @foreach ($menuCategories as $cat)
                    <div class="group relative">
                        <a href="{{ $cat->url }}" @if ($cat->children->isNotEmpty()) aria-haspopup="true" @endif @class(['flex items-center gap-1 px-3 py-3 transition group-hover:text-brand-600', 'text-brand-600' => $cat->isActive, 'text-brand-900' => ! $cat->isActive])>
                            {{ $cat->name }}
                            @if ($cat->children->isNotEmpty())
                                <x-heroicon-m-chevron-down class="size-4 opacity-50 transition group-hover:rotate-180" />
                            @endif
                        </a>
                        @if ($cat->children->isNotEmpty())
                            {{-- pt-2 là "cầu nối" vô hình: rê chuột từ menu cha xuống không bị rơi khỏi vùng hover. --}}
                            <div class="invisible absolute top-full left-0 z-30 min-w-64 translate-y-1 pt-2 opacity-0 transition duration-150 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                                <div class="relative rounded-2xl bg-white p-2 shadow-[var(--shadow-lift)] ring-1 ring-brand-900/10">
                                    <span class="absolute -top-1.5 left-8 size-3 rotate-45 rounded-sm bg-white ring-1 ring-brand-900/10 [clip-path:polygon(0_0,100%_0,0_100%)]"></span>
                                    <a href="{{ $cat->url }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[13px] font-bold text-brand-700 hover:bg-brand-50">
                                        Tất cả {{ mb_strtolower($cat->name) }} <x-heroicon-m-arrow-right class="size-4" />
                                    </a>
                                    <div class="my-1 border-t border-brand-900/5"></div>
                                    @foreach ($cat->children as $child)
                                        <a href="{{ $child->url }}" class="group/item flex items-center gap-2.5 rounded-xl px-3 py-2.5 font-medium text-ink/80 transition hover:bg-brand-50 hover:text-brand-800 focus-visible:bg-brand-50">
                                            <span class="size-1.5 rounded-full bg-brand-300 transition group-hover/item:bg-brand-600"></span>{{ $child->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
                <a href="{{ route('posts.index') }}" class="hidden px-3 py-3 text-brand-900 hover:text-brand-600 xl:block">Kiến thức</a>
                <a href="{{ route('contact') }}" class="hidden px-3 py-3 text-brand-900 hover:text-brand-600 xl:block">Liên hệ</a>
                <a href="{{ route('orders.track') }}" class="ml-auto flex items-center gap-1.5 px-3 py-3 text-brand-700 hover:text-brand-500">
                    <x-heroicon-o-truck class="size-4.5" /> Tra cứu đơn hàng
                </a>
            </div>
        </nav>
    </div>

    {{-- Menu trượt trên mobile: teleport ra body để không bị kẹt trong stacking context của header sticky. --}}
    <template x-teleport="body">
    <div x-show="mobileOpen" x-cloak @keydown.escape.window="mobileOpen = false" class="fixed inset-0 z-[70] lg:hidden" role="dialog" aria-modal="true" aria-label="Menu danh mục">
        <div x-show="mobileOpen" x-transition.opacity @click="mobileOpen = false" class="absolute inset-0 bg-brand-950/50 backdrop-blur-sm"></div>
        <div x-show="mobileOpen" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 flex w-[85%] max-w-sm flex-col overflow-y-auto bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-brand-900/5 p-4">
                <span class="font-extrabold text-brand-900">Danh mục</span>
                <button type="button" @click="mobileOpen = false" class="grid size-9 place-items-center rounded-full hover:bg-brand-50" aria-label="Đóng menu"><x-heroicon-o-x-mark class="size-5" /></button>
            </div>
            <nav class="flex-1 p-3 text-[15px] font-semibold">
                <a href="{{ route('products.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50"><x-heroicon-o-squares-2x2 class="size-5 text-brand-500" />Tất cả sản phẩm</a>
                @foreach ($menuCategories as $cat)
                    <div x-data="{ open: false }">
                        <div class="flex items-center">
                            <a href="{{ $cat->url }}" class="flex flex-1 items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50">
                                <span class="grid size-5 place-items-center text-brand-500">•</span>{{ $cat->name }}
                            </a>
                            @if ($cat->children->isNotEmpty())
                                <button type="button" @click="open = !open" class="grid size-10 place-items-center rounded-full hover:bg-brand-50" aria-label="Mở danh mục con"><x-heroicon-m-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" /></button>
                            @endif
                        </div>
                        @if ($cat->children->isNotEmpty())
                            <div x-show="open" x-collapse class="ml-11 border-l border-brand-100 pl-3">
                                @foreach ($cat->children as $child)
                                    <a href="{{ $child->url }}" class="block py-2 text-sm font-medium text-ink/70">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
                <div class="my-3 border-t border-brand-900/5"></div>
                <a href="{{ route('posts.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50"><x-heroicon-o-book-open class="size-5 text-brand-500" />Kiến thức nuôi ếch</a>
                <a href="{{ route('orders.track') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50"><x-heroicon-o-truck class="size-5 text-brand-500" />Tra cứu đơn hàng</a>
                <a href="{{ route('contact') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50"><x-heroicon-o-chat-bubble-left-right class="size-5 text-brand-500" />Liên hệ</a>
                @auth
                    <a href="{{ route('account.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50"><x-heroicon-o-user-circle class="size-5 text-brand-500" />Tài khoản của tôi</a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-brand-900 hover:bg-brand-50"><x-heroicon-o-user-circle class="size-5 text-brand-500" />Đăng nhập / Đăng ký</a>
                @endauth
            </nav>
            <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="m-4 mb-[max(1rem,env(safe-area-inset-bottom))] flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-accent-400 py-3.5 font-bold text-brand-950">
                <x-heroicon-s-phone class="size-5" /> Gọi {{ $phone }}
            </a>
        </div>
    </div>
    </template>
</header>
