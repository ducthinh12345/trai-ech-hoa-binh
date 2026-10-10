<div class="container-x py-8 lg:py-12">
    <h1 class="section-title">Giỏ hàng của bạn</h1>

    @if ($items->isEmpty())
        <div class="card mt-8 flex flex-col items-center px-6 py-16 text-center">
            <div class="grid size-24 place-items-center rounded-full bg-brand-50 text-brand-400"><x-heroicon-o-shopping-bag class="size-12" /></div>
            <h2 class="mt-6 text-xl font-bold">Giỏ hàng đang trống</h2>
            <p class="mt-2 max-w-sm text-sm text-gray-500">Chọn ếch giống khoẻ hay thịt ếch tươi ngon cho bữa cơm gia đình nhé!</p>
            <a href="{{ route('products.index') }}" class="btn-primary btn-lg mt-6">Khám phá sản phẩm</a>
        </div>
        @if ($suggestions->isNotEmpty())
            <h2 class="mt-14 mb-6 text-xl font-bold">Gợi ý cho bạn</h2>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($suggestions as $product)<x-product-card :product="$product" />@endforeach
            </div>
        @endif
    @else
        <div class="mt-8 grid gap-8 lg:grid-cols-12">
            <div class="space-y-4 lg:col-span-8">
                @if ($freeShipFrom > 0)
                    @php $remain = max(0, $freeShipFrom - $subtotal); $pct = min(100, $subtotal / $freeShipFrom * 100); @endphp
                    <div class="card p-4">
                        <p class="text-sm font-medium">
                            @if ($remain > 0)
                                Mua thêm <strong class="text-brand-700">{{ money($remain) }}</strong> để được <strong>miễn phí vận chuyển</strong> 🚚
                            @else
                                🎉 Đơn của bạn đã được <strong class="text-brand-700">miễn phí vận chuyển</strong>!
                            @endif
                        </p>
                        <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-brand-50"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-400 transition-all duration-500" style="width: {{ $pct }}%"></div></div>
                    </div>
                @endif

                <ul class="card divide-y divide-brand-900/5">
                    @foreach ($items as $key => $item)
                        <li wire:key="item-{{ $key }}" class="flex gap-4 p-4 sm:p-5">
                            <a href="{{ $item->product->url() }}" class="shrink-0"><img src="{{ $item->imageUrl() }}" alt="{{ $item->name() }}" class="size-20 rounded-xl object-cover sm:size-24" width="96" height="96"></a>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ $item->product->url() }}" class="line-clamp-2 font-bold hover:text-brand-700">{{ $item->name() }}</a>
                                        @if ($item->variantName())<p class="mt-0.5 text-sm text-gray-500">Quy cách: {{ $item->variantName() }}</p>@endif
                                        <p class="mt-0.5 text-sm text-gray-500">{{ money($item->unitPrice()) }} / {{ $item->variantName() ?? $item->product->unit }}</p>
                                        @isset($problems[$key])<p class="mt-1 text-xs font-semibold text-red-600">⚠ {{ $problems[$key] }}</p>@endisset
                                    </div>
                                    <button type="button" wire:click="remove('{{ $key }}')" class="grid size-8 shrink-0 place-items-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-500" aria-label="Xoá {{ $item->name() }}"><x-heroicon-o-trash class="size-4.5" /></button>
                                </div>
                                <div class="mt-auto flex items-center justify-between pt-3">
                                    <div class="flex items-center rounded-full ring-1 ring-brand-900/10">
                                        <button type="button" wire:click="updateQuantity('{{ $key }}', {{ $item->quantity - 1 }})" class="grid size-9 place-items-center rounded-full hover:bg-brand-50" aria-label="Giảm"><x-heroicon-m-minus class="size-3.5" /></button>
                                        <span class="w-10 text-center text-sm font-bold">{{ $item->quantity }}</span>
                                        <button type="button" wire:click="updateQuantity('{{ $key }}', {{ $item->quantity + 1 }})" class="grid size-9 place-items-center rounded-full hover:bg-brand-50" aria-label="Tăng"><x-heroicon-m-plus class="size-3.5" /></button>
                                    </div>
                                    <span class="text-base font-extrabold text-brand-700">{{ money($item->lineTotal()) }}</span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('products.index') }}" class="btn-ghost"><x-heroicon-m-arrow-left class="size-4" /> Tiếp tục mua sắm</a>
            </div>

            <aside class="lg:col-span-4">
                <div class="card sticky top-36 space-y-5 p-5 sm:p-6">
                    <h2 class="text-lg font-bold">Tóm tắt đơn hàng</h2>

                    <div>
                        @if ($coupon)
                            <div class="flex items-center justify-between rounded-xl bg-brand-50 px-4 py-3 ring-1 ring-brand-200">
                                <div><p class="text-sm font-bold text-brand-800">🎟 {{ $coupon->code }}</p><p class="text-xs text-brand-700">{{ $coupon->summary() }}</p></div>
                                <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-red-500 hover:underline">Gỡ</button>
                            </div>
                        @else
                            <form wire:submit="applyCoupon" class="flex gap-2">
                                <label for="coupon" class="sr-only">Mã giảm giá</label>
                                <input id="coupon" type="text" wire:model="couponCode" placeholder="Nhập mã giảm giá" class="input uppercase placeholder:normal-case">
                                <button class="btn-outline shrink-0" wire:loading.attr="disabled" wire:target="applyCoupon">Áp dụng</button>
                            </form>
                            @error('couponCode')<p class="field-error">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <dl class="space-y-2.5 border-t border-brand-900/5 pt-4 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Tạm tính</dt><dd class="font-semibold">{{ money($subtotal) }}</dd></div>
                        @if ($discount > 0)<div class="flex justify-between text-brand-700"><dt>Giảm giá</dt><dd class="font-semibold">−{{ money($discount) }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-gray-500">Phí vận chuyển</dt><dd class="text-gray-500">Tính ở bước sau</dd></div>
                    </dl>
                    <div class="flex items-end justify-between border-t border-brand-900/5 pt-4">
                        <span class="font-bold">Tổng tạm tính</span>
                        <span class="text-2xl font-extrabold text-brand-700">{{ money($subtotal - $discount) }}</span>
                    </div>
                    <a href="{{ route('checkout') }}" @class(['btn-primary btn-lg w-full', 'pointer-events-none opacity-50' => $problems])>Tiến hành đặt hàng <x-heroicon-m-arrow-right class="size-5" /></a>
                    @if ($problems)<p class="text-center text-xs text-red-600">Vui lòng điều chỉnh các sản phẩm được đánh dấu trước khi đặt hàng.</p>@endif
                    <ul class="space-y-2 text-xs text-gray-500">
                        <li class="flex items-center gap-2"><x-heroicon-o-shield-check class="size-4 text-brand-500" />Ếch chết khi nhận — đổi mới 100%</li>
                        <li class="flex items-center gap-2"><x-heroicon-o-truck class="size-4 text-brand-500" />Đóng gói oxy/giữ lạnh chuyên dụng</li>
                    </ul>
                </div>
            </aside>
        </div>
    @endif
</div>
