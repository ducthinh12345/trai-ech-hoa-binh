{{-- Nội dung bộ lọc dùng chung cho sidebar desktop và ngăn kéo mobile. --}}
<div class="card p-5">
    <h2 class="mb-3 text-sm font-bold tracking-wide text-brand-900 uppercase">Danh mục</h2>
    <ul class="space-y-0.5 text-sm">
        <li><a href="{{ route('products.index') }}" @class(['flex justify-between rounded-lg px-3 py-2 font-medium', 'bg-brand-700 text-white' => ! $category, 'hover:bg-brand-50' => $category])>Tất cả</a></li>
        @foreach ($categories as $cat)
            {{-- Danh mục con chỉ mở sẵn khi khách đang ở nhánh này; nhánh khác thu gọn, bấm mũi tên để mở. --}}
            <li x-data="{ open: @js($cat->isActiveBranch($category)) }">
                <div class="flex items-center gap-1">
                    <a href="{{ $cat->url() }}" @if ($category?->id === $cat->id) aria-current="page" @endif @class([
                        'flex flex-1 justify-between rounded-lg px-3 py-2 font-medium',
                        'bg-brand-700 text-white' => $category?->id === $cat->id,
                        'bg-brand-50 text-brand-800' => $category?->parent_id === $cat->id,
                        'hover:bg-brand-50' => ! $cat->isActiveBranch($category),
                    ])>
                        {{ $cat->name }} <span class="opacity-60">{{ $cat->products_count }}</span>
                    </a>
                    @if ($cat->children->isNotEmpty())
                        <button type="button" @click="open = !open" :aria-expanded="open" class="grid size-8 shrink-0 place-items-center rounded-lg text-gray-500 hover:bg-brand-50 hover:text-brand-700" aria-label="Danh mục con của {{ $cat->name }}">
                            <x-heroicon-m-chevron-down @class(['size-4 transition', 'rotate-180' => $cat->isActiveBranch($category)]) ::class="{ 'rotate-180': open }" />
                        </button>
                    @endif
                </div>
                @if ($cat->children->isNotEmpty())
                    <ul x-show="open" x-collapse @unless ($cat->isActiveBranch($category)) x-cloak @endunless class="mt-0.5 ml-3 border-l border-brand-100 pl-2">
                        @foreach ($cat->children as $child)
                            <li><a href="{{ $child->url() }}" @if ($category?->id === $child->id) aria-current="page" @endif @class(['block rounded-lg px-3 py-1.5', 'bg-brand-700/10 font-bold text-brand-800' => $category?->id === $child->id, 'text-gray-600 hover:text-brand-700' => $category?->id !== $child->id])>{{ $child->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>
<form method="GET" class="card space-y-4 p-5">
    @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    <h2 class="text-sm font-bold tracking-wide text-brand-900 uppercase">Khoảng giá</h2>
    <div class="flex items-center gap-2">
        <input type="number" name="min" value="{{ request('min') }}" placeholder="Từ" min="0" step="1000" class="input py-2" aria-label="Giá từ">
        <span class="text-gray-400">–</span>
        <input type="number" name="max" value="{{ request('max') }}" placeholder="Đến" min="0" step="1000" class="input py-2" aria-label="Giá đến">
    </div>
    <label class="flex items-center gap-2.5 text-sm font-medium">
        <input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock')) class="size-4 rounded accent-brand-700"> Chỉ hiện còn hàng
    </label>
    <div class="flex gap-2">
        <button class="btn-primary flex-1">Áp dụng</button>
        @if (request()->hasAny(['min', 'max', 'in_stock']))<a href="{{ url()->current() }}" class="btn-ghost">Xoá</a>@endif
    </div>
</form>

