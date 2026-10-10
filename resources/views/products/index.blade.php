<x-layouts.shop :seo="$seo">
    <div class="container-x py-8 lg:py-10" x-data="{ filtersOpen: false }" x-effect="document.documentElement.classList.toggle('overflow-hidden', filtersOpen)">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="section-title">
                    @if (request('q')) Kết quả cho “{{ request('q') }}” @else {{ $category?->name ?? 'Tất cả sản phẩm' }} @endif
                </h1>
                <p class="mt-1 text-sm text-gray-500">{{ $products->total() }} sản phẩm</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="filtersOpen = true" class="btn-outline lg:hidden"><x-heroicon-o-funnel class="size-4" /> Bộ lọc</button>
                <form method="GET" class="flex items-center gap-2">
                    @foreach (request()->except(['sort', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <label for="sort" class="hidden text-sm text-gray-500 sm:block">Sắp xếp</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="input w-auto rounded-full py-2.5 pl-5 font-medium">
                        @foreach ($sorts as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'popular') === $value)>{{ $label }}</option>@endforeach
                    </select>
                </form>
            </div>
        </div>

        @if ($category?->description)
            <p class="mt-4 max-w-3xl text-[15px] leading-7 text-ink/75">{{ $category->description }}</p>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-12">
            <aside class="hidden lg:col-span-3 lg:block">
                <div class="space-y-5 lg:sticky lg:top-40">
                    @include('products.partials.filters')
                </div>
            </aside>

            {{-- Ngăn kéo bộ lọc trên mobile, teleport ra body để nằm trên thanh điều hướng đáy và nút liên hệ nổi. --}}
            <template x-teleport="body">
                <div x-show="filtersOpen" x-cloak @keydown.escape.window="filtersOpen = false" class="fixed inset-0 z-[70] lg:hidden" role="dialog" aria-modal="true" aria-label="Bộ lọc">
                    <div x-show="filtersOpen" x-transition.opacity @click="filtersOpen = false" class="absolute inset-0 bg-brand-950/50 backdrop-blur-sm"></div>
                    <div x-show="filtersOpen" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="-translate-x-full"
                         class="absolute inset-y-0 left-0 flex w-[85%] max-w-sm flex-col bg-cream shadow-2xl">
                        <div class="flex items-center justify-between border-b border-brand-900/5 p-4">
                            <span class="text-lg font-bold">Bộ lọc</span>
                            <button type="button" @click="filtersOpen = false" class="grid size-9 place-items-center rounded-full hover:bg-brand-50" aria-label="Đóng bộ lọc"><x-heroicon-o-x-mark class="size-5" /></button>
                        </div>
                        <div class="flex-1 space-y-5 overflow-y-auto p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                            @include('products.partials.filters')
                        </div>
                    </div>
                </div>
            </template>

            <div class="lg:col-span-9">
                @if ($products->isEmpty())
                    <div class="card flex flex-col items-center px-6 py-16 text-center">
                        <x-heroicon-o-magnifying-glass class="size-14 text-brand-300" />
                        <h2 class="mt-4 text-lg font-bold">Không tìm thấy sản phẩm phù hợp</h2>
                        <p class="mt-1 text-sm text-gray-500">Thử từ khoá khác hoặc gọi hotline {{ setting('store_phone') }} để được tư vấn.</p>
                        <a href="{{ route('products.index') }}" class="btn-primary mt-5">Xem tất cả sản phẩm</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
                        @foreach ($products as $product)<x-product-card :product="$product" />@endforeach
                    </div>
                    <div class="mt-10">{{ $products->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.shop>
