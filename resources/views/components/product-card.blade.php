@props(['product'])
@php
    $discount = $product->discountPercent();
    $compareAt = $product->displayCompareAtPrice();
    $inStock = $product->isInStock();
    $variantCount = $product->variants->where('is_active', true)->count();
@endphp
<article {{ $attributes->merge(['class' => 'group card relative flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-[var(--shadow-lift)]']) }}>
    <a href="{{ $product->url() }}" class="relative block aspect-square overflow-hidden bg-brand-50">
        <img src="{{ $product->thumbnailUrl() }}" alt="{{ $product->name }}" loading="lazy" width="400" height="400"
             class="size-full object-cover transition duration-500 group-hover:scale-105">
        <div class="absolute top-3 left-3 flex flex-col items-start gap-1.5">
            @if ($discount)
                <span class="badge bg-red-500 text-white shadow">-{{ $discount }}%</span>
            @endif
            @if ($product->is_featured)
                <span class="badge bg-accent-400 text-brand-950 shadow">Bán chạy</span>
            @endif
        </div>
        @unless ($inStock)
            <div class="absolute inset-0 grid place-items-center bg-white/60 backdrop-blur-[2px]">
                <span class="badge bg-gray-900 px-3 py-1 text-white">Tạm hết hàng</span>
            </div>
        @endunless
    </a>
    <div class="flex flex-1 flex-col p-4">
        @if ($product->relationLoaded('category') && $product->category)
            <span class="mb-1 text-[11px] font-semibold tracking-wide text-brand-500 uppercase">{{ $product->category->name }}</span>
        @endif
        <h3 class="line-clamp-2 text-[15px] leading-snug font-bold text-ink">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 after:content-[''] hover:text-brand-700">{{ $product->name }}</a>
        </h3>
        @if ($product->rating_count > 0)
            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-500">
                <x-stars :rating="$product->rating_average" size="size-3.5" />
                <span>({{ $product->rating_count }})</span>
                @if ($product->sold_count)
                    <span class="text-gray-300">|</span><span>Đã bán {{ number_format($product->sold_count, 0, ',', '.') }}</span>
                @endif
            </div>
        @elseif ($product->sold_count)
            <p class="mt-1.5 text-xs text-gray-500">Đã bán {{ number_format($product->sold_count, 0, ',', '.') }}</p>
        @endif
        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <div>
                <p class="text-lg leading-none font-extrabold text-brand-700">
                    @if ($variantCount > 1)<span class="text-xs font-semibold text-gray-500">Từ </span>@endif{{ money($product->displayPrice()) }}
                </p>
                <p class="mt-1 flex items-center gap-1.5 text-xs">
                    @if ($compareAt && $discount)<del class="text-gray-400">{{ money($compareAt) }}</del>@endif
                    <span class="text-gray-500">/ {{ $variantCount ? $product->defaultVariant()?->name : $product->unit }}</span>
                </p>
            </div>
            <div class="relative z-10">
                @staticdemo
                    <a href="{{ $product->url() }}" class="grid size-10 place-items-center rounded-full bg-brand-700 text-white shadow-sm transition hover:scale-110 hover:bg-brand-800" aria-label="Xem {{ $product->name }}"><x-heroicon-m-arrow-right class="size-5" /></a>
                @else
                    <livewire:quick-add :product="$product" :key="'qa-'.$product->id" />
                @endstaticdemo
            </div>
        </div>
    </div>
</article>
