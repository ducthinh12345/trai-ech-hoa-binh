<x-layouts.shop :seo="$seo">
    @php $images = $product->imageUrls() ?: [$product->thumbnailUrl()]; @endphp
    <div class="container-x py-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
            {{-- Gallery --}}
            <div x-data="gallery({{ count($images) }})" class="lg:sticky lg:top-36 lg:self-start">
                <div class="card relative aspect-square overflow-hidden" @touchstart.passive="touchStart" @touchend="touchEnd">
                    @foreach ($images as $i => $img)
                        <img x-show="active === {{ $i }}" @if ($i > 0) x-cloak loading="lazy" @else fetchpriority="high" @endif src="{{ $img }}" alt="{{ $product->name }}{{ $i ? ' – ảnh '.($i + 1) : '' }}" class="size-full object-cover" width="800" height="800">
                    @endforeach
                    @if ($d = $product->discountPercent())<span class="badge absolute top-4 left-4 bg-red-500 px-3 py-1 text-sm text-white shadow">-{{ $d }}%</span>@endif
                    @if (count($images) > 1)
                        <button type="button" @click="go(active - 1)" class="absolute top-1/2 left-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow hover:bg-white" aria-label="Ảnh trước"><x-heroicon-m-chevron-left class="size-5" /></button>
                        <button type="button" @click="go(active + 1)" class="absolute top-1/2 right-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow hover:bg-white" aria-label="Ảnh sau"><x-heroicon-m-chevron-right class="size-5" /></button>
                    @endif
                </div>
                @if (count($images) > 1)
                    <div class="mt-3 flex gap-3 overflow-x-auto scrollbar-none">
                        @foreach ($images as $i => $img)
                            <button type="button" @click="go({{ $i }})" class="size-20 shrink-0 overflow-hidden rounded-xl ring-2 transition" :class="active === {{ $i }} ? 'ring-brand-600' : 'ring-transparent opacity-70 hover:opacity-100'" aria-label="Xem ảnh {{ $i + 1 }}">
                                <img src="{{ $img }}" alt="" class="size-full object-cover" loading="lazy" width="80" height="80">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Thông tin --}}
            <div>
                @if ($product->category)<a href="{{ $product->category->url() }}" class="section-eyebrow hover:underline">{{ $product->category->name }}</a>@endif
                <h1 class="mt-2 text-3xl leading-tight font-extrabold text-brand-950 text-balance sm:text-4xl">{{ $product->name }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                    @if ($product->rating_count > 0)
                        <a href="#danh-gia" class="flex items-center gap-1.5"><x-stars :rating="$product->rating_average" /><span class="font-semibold text-ink">{{ number_format($product->rating_average, 1) }}</span>({{ $product->rating_count }} đánh giá)</a>
                    @endif
                    @if ($product->sold_count)<span>Đã bán <strong class="text-ink">{{ number_format($product->sold_count, 0, ',', '.') }}</strong></span>@endif
                    @if ($product->sku)<span>Mã: {{ $product->sku }}</span>@endif
                </div>
                @if ($product->short_description)<p class="mt-5 text-[15.5px] leading-7 text-ink/80">{{ $product->short_description }}</p>@endif

                <div class="mt-6"><livewire:add-to-cart :product="$product" /></div>

                <div class="mt-6 flex items-center gap-3">
                    @auth
                        @php $wished = auth()->user()->wishlistProducts()->whereKey($product->id)->exists(); @endphp
                        <form action="{{ route('account.wishlist.toggle', $product) }}" method="POST">@csrf
                            <button class="btn-ghost {{ $wished ? 'text-red-500' : '' }}">
                                <x-dynamic-component :component="$wished ? 'heroicon-s-heart' : 'heroicon-o-heart'" class="size-5" /> {{ $wished ? 'Đã yêu thích' : 'Yêu thích' }}
                            </button>
                        </form>
                    @endauth
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($product->url()) }}" target="_blank" rel="noopener" class="btn-ghost"><x-heroicon-o-share class="size-5" /> Chia sẻ</a>
                </div>

                <ul class="mt-6 grid gap-3 rounded-2xl bg-white p-5 text-sm ring-1 ring-brand-900/5 sm:grid-cols-2">
                    <li class="flex items-center gap-3"><x-heroicon-o-truck class="size-6 shrink-0 text-brand-600" />Giao toàn quốc 1–3 ngày</li>
                    <li class="flex items-center gap-3"><x-heroicon-o-shield-check class="size-6 shrink-0 text-brand-600" />Bù hàng nếu hao hụt khi nhận</li>
                    <li class="flex items-center gap-3"><x-heroicon-o-banknotes class="size-6 shrink-0 text-brand-600" />Kiểm hàng rồi mới thanh toán</li>
                    <li class="flex items-center gap-3"><x-heroicon-o-phone class="size-6 shrink-0 text-brand-600" />Tư vấn kỹ thuật miễn phí</li>
                </ul>
            </div>
        </div>

        {{-- Mô tả & thông số --}}
        <div x-data="{ tab: 'desc' }" class="mt-14">
            <div class="flex gap-1 overflow-x-auto border-b border-brand-900/10 scrollbar-none" role="tablist">
                <button type="button" role="tab" @click="tab = 'desc'" :class="tab === 'desc' ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500'" class="shrink-0 border-b-2 px-5 py-3 font-bold transition">Mô tả sản phẩm</button>
                @if ($product->specifications)<button type="button" role="tab" @click="tab = 'spec'" :class="tab === 'spec' ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500'" class="shrink-0 border-b-2 px-5 py-3 font-bold transition">Thông số</button>@endif
                <button type="button" role="tab" @click="tab = 'review'" :class="tab === 'review' ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500'" class="shrink-0 border-b-2 px-5 py-3 font-bold transition">Đánh giá ({{ $product->rating_count }})</button>
            </div>
            <div class="card mt-6 p-6 sm:p-8">
                <div x-show="tab === 'desc'" class="prose-content max-w-3xl">{!! $product->description ?: '<p>Đang cập nhật mô tả.</p>' !!}</div>
                @if ($product->specifications)
                    <div x-show="tab === 'spec'" x-cloak>
                        <table class="w-full max-w-2xl overflow-hidden rounded-xl text-sm ring-1 ring-brand-900/10">
                            @foreach ($product->specifications as $spec)
                                <tr class="odd:bg-brand-50/50"><th class="w-1/3 px-4 py-3 text-left font-semibold text-brand-900">{{ $spec['label'] ?? '' }}</th><td class="px-4 py-3">{{ $spec['value'] ?? '' }}</td></tr>
                            @endforeach
                        </table>
                    </div>
                @endif
                <div x-show="tab === 'review'" x-cloak id="danh-gia">
                    @include('products.partials.reviews')
                </div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-16">
                <x-section-heading title="Sản phẩm liên quan" :link="$product->category?->url()" />
                <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($related as $item)<x-product-card :product="$item" />@endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.shop>
