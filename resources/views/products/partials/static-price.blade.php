{{-- Bản demo tĩnh: không có giỏ hàng, hiện bảng giá theo quy cách và kênh đặt hàng trực tiếp. --}}
<div class="mt-6 space-y-5">
    <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-accent-50 p-5 ring-1 ring-brand-100">
        <div class="flex flex-wrap items-end gap-x-3 gap-y-1">
            <span class="text-3xl font-extrabold text-brand-700 sm:text-4xl">{{ money($product->displayPrice()) }}</span>
            <span class="pb-1 text-sm text-gray-500">/ {{ $product->variants->isNotEmpty() ? $product->defaultVariant()?->name : $product->unit }}</span>
            @if (($compare = $product->displayCompareAtPrice()) && $compare > $product->displayPrice())
                <del class="pb-1 text-base text-gray-400">{{ money($compare) }}</del>
            @endif
        </div>
    </div>
    @if ($product->variants->isNotEmpty())
        <div>
            <p class="label">Bảng giá theo quy cách</p>
            <ul class="divide-y divide-brand-900/5 overflow-hidden rounded-xl bg-white ring-1 ring-brand-900/10">
                @foreach ($product->variants as $v)
                    <li class="flex items-center justify-between px-4 py-3 text-sm"><span class="font-semibold">{{ $v->name }}</span><span class="font-bold text-brand-700">{{ money($v->price) }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="grid gap-3 sm:grid-cols-2">
        <a href="tel:{{ preg_replace('/\D/', '', setting('store_phone', '')) }}" class="btn-primary btn-lg"><x-heroicon-s-phone class="size-5" /> Gọi đặt hàng</a>
        <a href="https://zalo.me/{{ preg_replace('/\D/', '', (string) setting('store_zalo', setting('store_phone'))) }}" target="_blank" rel="noopener" class="btn-outline btn-lg border-2 border-brand-600">Nhắn Zalo</a>
    </div>
</div>
