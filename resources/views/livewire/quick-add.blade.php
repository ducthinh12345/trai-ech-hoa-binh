<div>
    @if (! $inStock)
        <span class="grid size-10 place-items-center rounded-full bg-gray-100 text-gray-400" title="Tạm hết hàng"><x-heroicon-o-no-symbol class="size-5" /></span>
    @elseif ($needsOptions)
        <a href="{{ $url }}" class="grid size-10 place-items-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition hover:bg-brand-700 hover:text-white" title="Chọn quy cách" aria-label="Chọn quy cách">
            <x-heroicon-o-adjustments-horizontal class="size-5" />
        </a>
    @else
        <button type="button" wire:click="add" wire:loading.attr="disabled" class="grid size-10 place-items-center rounded-full bg-brand-700 text-white shadow-sm transition hover:scale-110 hover:bg-brand-800 disabled:opacity-60" title="Thêm vào giỏ" aria-label="Thêm vào giỏ">
            <x-heroicon-o-plus wire:loading.remove class="size-5" />
            <svg wire:loading class="size-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </button>
    @endif
</div>
