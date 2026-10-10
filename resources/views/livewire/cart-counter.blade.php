<a href="{{ route('cart.index') }}" class="relative flex items-center gap-2.5 rounded-full py-1.5 pr-1.5 pl-1.5 text-brand-900 hover:bg-brand-50 sm:pr-4" aria-label="Giỏ hàng ({{ $count }} sản phẩm)">
    <span class="relative grid size-9 place-items-center rounded-full bg-brand-700 text-white">
        <x-heroicon-o-shopping-bag class="size-5" />
        @if ($count > 0)
            <span wire:key="count-{{ $count }}" class="animate-toast-in absolute -top-1 -right-1 grid h-5 min-w-5 place-items-center rounded-full bg-accent-400 px-1 text-[11px] font-extrabold text-brand-950 ring-2 ring-white">{{ $count > 99 ? '99+' : $count }}</span>
        @endif
    </span>
    <span class="hidden leading-tight sm:block">
        <span class="block text-[11px] text-gray-500">Giỏ hàng</span>
        <span class="block text-sm font-bold">{{ money($subtotal) }}</span>
    </span>
</a>
