@props(['seo'])
<x-layouts.shop :seo="$seo">
    <div class="container-x py-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-4">
            <aside>
                <div class="card p-5">
                    <div class="flex items-center gap-3 border-b border-brand-900/5 pb-4">
                        <span class="grid size-12 place-items-center rounded-full bg-brand-700 text-lg font-bold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <div class="min-w-0"><p class="truncate font-bold">{{ auth()->user()->name }}</p><p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p></div>
                    </div>
                    <nav class="mt-3 space-y-0.5 text-sm font-semibold">
                        @foreach ([['account.index', 'shopping-bag', 'Đơn hàng của tôi'], ['account.wishlist', 'heart', 'Yêu thích'], ['account.addresses', 'map-pin', 'Sổ địa chỉ'], ['account.profile', 'user', 'Thông tin & mật khẩu']] as [$r, $i, $l])
                            <a href="{{ route($r) }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5', 'bg-brand-50 text-brand-800' => request()->routeIs($r), 'text-ink/70 hover:bg-brand-50' => ! request()->routeIs($r)])><x-dynamic-component :component="'heroicon-o-'.$i" class="size-5" />{{ $l }}</a>
                        @endforeach
                        @if (auth()->user()->role->canAccessAdminPanel())
                            <a href="/admin" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-accent-700 hover:bg-accent-50"><x-heroicon-o-cog-6-tooth class="size-5" />Trang quản trị</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-red-500 hover:bg-red-50"><x-heroicon-o-arrow-right-on-rectangle class="size-5" />Đăng xuất</button></form>
                    </nav>
                </div>
            </aside>
            <div class="lg:col-span-3">{{ $slot }}</div>
        </div>
    </div>
</x-layouts.shop>
