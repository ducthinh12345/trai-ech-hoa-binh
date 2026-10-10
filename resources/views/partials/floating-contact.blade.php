{{-- Nút gọi/Zalo nổi: khách nông nghiệp ưu tiên gọi điện và nhắn Zalo hơn điền form. --}}
@php
    $phone = preg_replace('/\D/', '', (string) setting('store_phone', ''));
    $zalo = preg_replace('/\D/', '', (string) setting('store_zalo', $phone));
@endphp
<div class="fixed right-4 bottom-24 z-30 flex flex-col gap-3 lg:bottom-6">
    @if ($zalo)
        <a href="https://zalo.me/{{ $zalo }}" target="_blank" rel="noopener" class="grid size-12 place-items-center rounded-full bg-[#0068ff] text-xs font-extrabold text-white shadow-lg ring-4 ring-white transition hover:scale-110" aria-label="Chat Zalo">Zalo</a>
    @endif
    @if ($phone)
        <a href="tel:{{ $phone }}" class="relative grid size-12 place-items-center rounded-full bg-brand-600 text-white shadow-lg ring-4 ring-white transition hover:scale-110" aria-label="Gọi điện">
            <span class="absolute inset-0 animate-ping rounded-full bg-brand-500 opacity-30"></span>
            <x-heroicon-s-phone class="relative size-5" />
        </a>
    @endif
</div>
