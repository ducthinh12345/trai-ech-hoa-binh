@props(['eyebrow' => null, 'title', 'link' => null, 'linkText' => 'Xem tất cả'])
<div {{ $attributes->merge(['class' => 'mb-8 flex items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)<p class="section-eyebrow">{{ $eyebrow }}</p>@endif
        <h2 class="section-title mt-1.5 text-balance">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="group hidden shrink-0 items-center gap-1.5 text-sm font-bold text-brand-700 hover:text-brand-500 sm:flex">
            {{ $linkText }} <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" />
        </a>
    @endif
</div>
