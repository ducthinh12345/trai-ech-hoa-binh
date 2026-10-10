@props(['items' => []])
<nav aria-label="Breadcrumb" class="border-b border-brand-900/5 bg-white/60">
    <ol class="container-x flex items-center gap-1.5 overflow-x-auto py-3 text-sm whitespace-nowrap scrollbar-none">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <x-heroicon-m-chevron-right class="size-3.5 text-gray-400" />
                @endif
                @if ($loop->last)
                    <span class="max-w-[16rem] truncate font-medium text-brand-900" aria-current="page">{{ $item['name'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="text-gray-500 transition hover:text-brand-700">{{ $item['name'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
