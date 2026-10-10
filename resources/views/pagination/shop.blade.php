@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang" class="flex items-center justify-center gap-1.5">
        @if ($paginator->onFirstPage())
            <span class="grid size-10 place-items-center rounded-full text-gray-300"><x-heroicon-m-chevron-left class="size-5" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-full bg-white ring-1 ring-brand-900/10 hover:bg-brand-50" aria-label="Trang trước"><x-heroicon-m-chevron-left class="size-5" /></a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-gray-400">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="grid size-10 place-items-center rounded-full bg-brand-700 text-sm font-bold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="grid size-10 place-items-center rounded-full bg-white text-sm font-semibold ring-1 ring-brand-900/10 hover:bg-brand-50">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-full bg-white ring-1 ring-brand-900/10 hover:bg-brand-50" aria-label="Trang sau"><x-heroicon-m-chevron-right class="size-5" /></a>
        @else
            <span class="grid size-10 place-items-center rounded-full text-gray-300"><x-heroicon-m-chevron-right class="size-5" /></span>
        @endif
    </nav>
@endif
