<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-accent-50 p-5 ring-1 ring-brand-100">
        <div class="flex flex-wrap items-end gap-x-3 gap-y-1">
            <span class="text-3xl font-extrabold text-brand-700 sm:text-4xl">{{ money($price) }}</span>
            <span class="pb-1 text-sm text-gray-500">/ {{ $variant?->name ?? $product->unit }}</span>
            @if ($compareAt && $compareAt > $price)
                <del class="pb-1 text-base text-gray-400">{{ money($compareAt) }}</del>
                <span class="badge mb-1.5 bg-red-500 text-white">Tiết kiệm {{ money($compareAt - $price) }}</span>
            @endif
        </div>
        <div class="mt-2 flex items-center gap-2 text-sm">
            @if ($stock === null || $stock > 0)
                <span class="flex items-center gap-1.5 font-semibold text-brand-700"><span class="size-2 rounded-full bg-brand-500"></span>Còn hàng</span>
                @if ($stock !== null && $stock <= $product->low_stock_threshold)
                    <span class="text-accent-700">— chỉ còn {{ $stock }} {{ $product->unit }}, đặt sớm kẻo hết!</span>
                @endif
            @else
                <span class="flex items-center gap-1.5 font-semibold text-red-600"><span class="size-2 rounded-full bg-red-500"></span>Tạm hết hàng — gọi hotline để đặt trước</span>
            @endif
        </div>
    </div>

    @if ($product->variants->count() > 0)
        <fieldset>
            <legend class="label">Chọn quy cách</legend>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($product->variants as $v)
                    @php $soldOut = $product->track_stock && $v->stock_quantity <= 0; @endphp
                    <label wire:key="v-{{ $v->id }}" @class([
                        'relative flex cursor-pointer flex-col rounded-xl border-2 px-3 py-2.5 transition',
                        'border-brand-600 bg-brand-50 shadow-sm' => $variantId === $v->id,
                        'border-brand-900/10 bg-white hover:border-brand-300' => $variantId !== $v->id,
                        'cursor-not-allowed opacity-50' => $soldOut,
                    ])>
                        <input type="radio" wire:model.live="variantId" value="{{ $v->id }}" class="sr-only" @disabled($soldOut)>
                        <span class="text-sm font-bold text-ink">{{ $v->name }}</span>
                        <span class="text-xs font-semibold text-brand-700">{{ money($v->price) }}</span>
                        @if ($soldOut)<span class="text-[10px] font-semibold text-red-500">Hết hàng</span>@endif
                        @if ($variantId === $v->id)
                            <x-heroicon-s-check-circle class="absolute top-1.5 right-1.5 size-4.5 text-brand-600" />
                        @endif
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center rounded-full bg-white ring-1 ring-brand-900/10">
            <button type="button" wire:click="decrement" class="grid size-12 place-items-center rounded-full text-brand-800 hover:bg-brand-50" aria-label="Giảm số lượng"><x-heroicon-m-minus class="size-4" /></button>
            <input type="number" wire:model.blur="quantity" min="{{ $product->min_order_quantity }}" class="w-14 border-0 bg-transparent text-center text-base font-bold focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" aria-label="Số lượng">
            <button type="button" wire:click="increment" class="grid size-12 place-items-center rounded-full text-brand-800 hover:bg-brand-50" aria-label="Tăng số lượng"><x-heroicon-m-plus class="size-4" /></button>
        </div>
        @if ($product->min_order_quantity > 1)
            <span class="text-xs text-gray-500">Tối thiểu {{ $product->min_order_quantity }} {{ $product->unit }}</span>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <button type="button" wire:click="add" wire:loading.attr="disabled" @disabled($stock !== null && $stock <= 0) class="btn-outline btn-lg border-2 border-brand-600">
            <x-heroicon-o-shopping-bag class="size-5" /> Thêm vào giỏ
        </button>
        <button type="button" wire:click="add(true)" wire:loading.attr="disabled" @disabled($stock !== null && $stock <= 0) class="btn-primary btn-lg">
            <span wire:loading.remove wire:target="add(true)">Mua ngay</span>
            <span wire:loading wire:target="add(true)">Đang xử lý...</span>
        </button>
    </div>
</div>
