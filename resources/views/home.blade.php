<x-layouts.shop :seo="$seo">
    {{-- HERO --}}
    <section class="relative overflow-hidden">
        @if ($heroBanners->isNotEmpty())
            <div x-data="slider({{ $heroBanners->count() }})" @mouseenter="stop" @mouseleave="start" class="relative">
                @foreach ($heroBanners as $i => $banner)
                    <div x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif x-transition:enter="transition duration-700" x-transition:enter-start="opacity-0" class="relative">
                        <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="h-[440px] w-full object-cover sm:h-[520px] lg:h-[600px]" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif width="1600" height="600">
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-950/85 via-brand-950/50 to-transparent"></div>
                        <div class="absolute inset-0">
                            <div class="container-x flex h-full items-center">
                                <div class="max-w-xl text-white">
                                    <span class="badge animate-fade-up bg-accent-400/95 px-3 py-1 text-brand-950">🐸 Trực tiếp từ trại</span>
                                    @if ($i === 0)
                                        <h1 class="animate-fade-up mt-4 text-4xl leading-[1.1] font-extrabold text-balance sm:text-5xl lg:text-6xl">{{ $banner->title }}</h1>
                                    @else
                                        <p class="animate-fade-up mt-4 text-4xl leading-[1.1] font-extrabold text-balance sm:text-5xl lg:text-6xl">{{ $banner->title }}</p>
                                    @endif
                                    @if ($banner->subtitle)<p class="animate-fade-up mt-4 text-base text-brand-100 [animation-delay:100ms] sm:text-lg">{{ $banner->subtitle }}</p>@endif
                                    <div class="animate-fade-up mt-8 flex flex-wrap gap-3 [animation-delay:200ms]">
                                        @if ($banner->link)<a href="{{ $banner->link }}" class="btn-accent btn-lg">{{ $banner->button_text ?: 'Mua ngay' }} <x-heroicon-m-arrow-right class="size-5" /></a>@endif
                                        <a href="tel:{{ preg_replace('/\D/', '', setting('store_phone', '')) }}" class="btn btn-lg bg-white/10 text-white ring-1 ring-white/30 backdrop-blur hover:bg-white/20"><x-heroicon-s-phone class="size-5" /> Tư vấn miễn phí</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if ($heroBanners->count() > 1)
                    <div class="absolute inset-x-0 bottom-6 flex justify-center gap-2">
                        @foreach ($heroBanners as $i => $b)
                            <button type="button" @click="active = {{ $i }}" class="h-2 rounded-full transition-all" :class="active === {{ $i }} ? 'w-8 bg-accent-400' : 'w-2 bg-white/50'" aria-label="Banner {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="bg-gradient-to-br from-brand-800 to-brand-950 py-24 text-white">
                <div class="container-x"><h1 class="max-w-2xl text-5xl font-extrabold">{{ setting('store_name', config('app.name')) }}</h1></div>
            </div>
        @endif
    </section>

    {{-- CAM KẾT --}}
    <section class="relative z-10 -mt-10">
        <div class="container-x">
            <div class="card grid grid-cols-2 divide-brand-900/5 lg:grid-cols-4 lg:divide-x">
                @foreach ([
                    ['icon' => 'shield-check', 'title' => 'Giống chuẩn, khoẻ mạnh', 'text' => 'Tuyển chọn, xử lý bệnh trước khi xuất trại'],
                    ['icon' => 'truck', 'title' => 'Giao sống toàn quốc', 'text' => 'Đóng thùng oxy, cam kết tỷ lệ sống'],
                    ['icon' => 'academic-cap', 'title' => 'Hỗ trợ kỹ thuật trọn đời', 'text' => 'Kỹ sư thuỷ sản tư vấn 24/7'],
                    ['icon' => 'arrow-path', 'title' => 'Đổi mới nếu hao hụt', 'text' => 'Bù hàng ếch chết khi nhận'],
                ] as $usp)
                    <div class="flex items-center gap-3.5 p-4 sm:p-6">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-600 sm:size-12"><x-dynamic-component :component="'heroicon-o-'.$usp['icon']" class="size-6" /></span>
                        <div><h2 class="text-sm font-bold text-brand-950 sm:text-[15px]">{{ $usp['title'] }}</h2><p class="mt-0.5 hidden text-xs text-gray-500 sm:block">{{ $usp['text'] }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- DANH MỤC --}}
    @if ($categories->isNotEmpty())
        <section class="container-x pt-16 lg:pt-20">
            <x-section-heading eyebrow="Danh mục" title="Bạn đang tìm gì hôm nay?" :link="route('products.index')" />
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($categories as $cat)
                    <a href="{{ $cat->url() }}" class="group card relative aspect-[4/5] overflow-hidden">
                        @if ($cat->imageUrl())<img src="{{ $cat->imageUrl() }}" alt="{{ $cat->name }}" loading="lazy" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-110" width="300" height="375">@endif
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-950/90 via-brand-950/20 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                            <h3 class="text-lg leading-tight font-extrabold">{{ $cat->name }}</h3>
                            <p class="mt-1 flex items-center gap-1 text-xs font-medium text-brand-200">{{ $cat->products_count }} sản phẩm <x-heroicon-m-arrow-right class="size-3.5 transition group-hover:translate-x-1" /></p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- NỔI BẬT --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="container-x pt-16 lg:pt-20">
            <x-section-heading eyebrow="Được tin chọn" title="Sản phẩm nổi bật" :link="route('products.index')" />
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($featuredProducts->take(max(4, intdiv($featuredProducts->count(), 4) * 4)) as $product)<x-product-card :product="$product" />@endforeach
            </div>
        </section>
    @endif

    {{-- PROMO --}}
    @if ($promoBanners->isNotEmpty())
        <section class="container-x pt-16 lg:pt-20">
            <div class="grid gap-5 {{ $promoBanners->count() > 1 ? 'md:grid-cols-2' : '' }}">
                @foreach ($promoBanners as $promo)
                    <a href="{{ $promo->link ?: '#' }}" class="group relative block overflow-hidden rounded-3xl">
                        <img src="{{ $promo->imageUrl() }}" alt="{{ $promo->title }}" loading="lazy" class="h-56 w-full object-cover transition duration-700 group-hover:scale-105 sm:h-64" width="800" height="256">
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-950/80 to-transparent"></div>
                        <div class="absolute inset-y-0 left-0 flex max-w-xs flex-col justify-center p-7 text-white">
                            <h3 class="text-2xl leading-tight font-extrabold">{{ $promo->title }}</h3>
                            @if ($promo->subtitle)<p class="mt-2 text-sm text-brand-100">{{ $promo->subtitle }}</p>@endif
                            <span class="btn-accent mt-4 self-start">{{ $promo->button_text ?: 'Xem ngay' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- BÁN CHẠY --}}
    @if ($bestSellers->isNotEmpty())
        <section class="container-x pt-16 lg:pt-20">
            <x-section-heading eyebrow="Top bán chạy" title="Khách hàng mua nhiều nhất" :link="route('products.index', ['sort' => 'popular'])" />
            <div class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 scrollbar-none sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-5 sm:overflow-visible sm:px-0 lg:grid-cols-4">
                @foreach ($bestSellers as $product)<x-product-card :product="$product" class="w-[46%] shrink-0 snap-start sm:w-auto" />@endforeach
            </div>
        </section>
    @endif

    {{-- VỀ TRẠI --}}
    <section class="pt-16 lg:pt-24">
        <div class="container-x">
            <div class="grid items-center gap-10 overflow-hidden rounded-[2rem] bg-brand-900 text-white lg:grid-cols-2">
                <div class="p-8 sm:p-12">
                    <p class="text-xs font-bold tracking-[0.18em] text-accent-300 uppercase">Câu chuyện của trại</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-balance sm:text-4xl">{{ setting('about_title', 'Hơn 10 năm gắn bó với nghề nuôi ếch') }}</h2>
                    <p class="mt-5 leading-7 text-brand-100">{{ setting('about_text', 'Từ vài ao đất nhỏ, trại đã phát triển thành cơ sở sản xuất ếch giống quy mô với quy trình khép kín: chọn ếch bố mẹ, ấp trứng, ương nòng nọc đến nuôi thương phẩm. Mỗi con ếch rời trại đều được kiểm tra sức khoẻ kỹ lưỡng.') }}</p>
                    <dl class="mt-8 grid grid-cols-3 gap-4 border-t border-white/10 pt-8">
                        @foreach ([['stat_years', '10+', 'năm kinh nghiệm'], ['stat_customers', '5.000+', 'hộ nuôi tin dùng'], ['stat_provinces', '34', 'tỉnh thành giao hàng']] as [$key, $default, $label])
                            <div><dt class="sr-only">{{ $label }}</dt><dd class="text-3xl font-extrabold text-accent-300 sm:text-4xl">{{ setting($key, $default) }}</dd><p class="mt-1 text-xs text-brand-200 sm:text-sm">{{ $label }}</p></div>
                        @endforeach
                    </dl>
                    <a href="{{ route('contact') }}" class="btn-accent btn-lg mt-8">Đặt lịch tham quan trại</a>
                </div>
                <div class="relative h-72 lg:h-full lg:min-h-[480px]">
                    <img src="{{ setting('about_image') ? asset('storage/'.setting('about_image')) : asset('images/farm.svg') }}" alt="Trại ếch" loading="lazy" class="absolute inset-0 size-full object-cover" width="720" height="480">
                </div>
            </div>
        </div>
    </section>

    {{-- MỚI VỀ --}}
    @if ($newProducts->isNotEmpty())
        <section class="container-x pt-16 lg:pt-20">
            <x-section-heading eyebrow="Mới lên kệ" title="Sản phẩm mới" :link="route('products.index', ['sort' => 'newest'])" />
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($newProducts as $product)<x-product-card :product="$product" />@endforeach
            </div>
        </section>
    @endif

    {{-- ĐÁNH GIÁ --}}
    @if ($testimonials->isNotEmpty())
        <section class="mt-16 bg-gradient-to-b from-brand-50/70 to-transparent py-16 lg:mt-24 lg:py-20">
            <div class="container-x">
                <div class="mx-auto mb-10 max-w-2xl text-center">
                    <p class="section-eyebrow">Khách hàng nói gì</p>
                    <h2 class="section-title mt-1.5">Hàng nghìn hộ nuôi đã thành công cùng trại</h2>
                </div>
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $t)
                        <figure class="card flex flex-col p-6">
                            <x-stars :rating="$t->rating" />
                            <blockquote class="mt-4 flex-1 text-[15px] leading-7 text-ink/80">“{{ $t->content }}”</blockquote>
                            <figcaption class="mt-5 flex items-center gap-3 border-t border-brand-900/5 pt-4">
                                @if ($t->avatarUrl())
                                    <img src="{{ $t->avatarUrl() }}" alt="" class="size-11 rounded-full object-cover" loading="lazy" width="44" height="44">
                                @else
                                    <span class="grid size-11 place-items-center rounded-full bg-brand-600 font-bold text-white">{{ mb_substr($t->customer_name, 0, 1) }}</span>
                                @endif
                                <div><p class="font-bold">{{ $t->customer_name }}</p>@if ($t->customer_title)<p class="text-xs text-gray-500">{{ $t->customer_title }}</p>@endif</div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- BLOG --}}
    @if ($posts->isNotEmpty())
        <section class="container-x pt-16 lg:pt-8">
            <x-section-heading eyebrow="Kiến thức" title="Kỹ thuật nuôi ếch & món ngon" :link="route('posts.index')" />
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($posts as $post)<x-post-card :post="$post" />@endforeach
            </div>
        </section>
    @endif
</x-layouts.shop>
