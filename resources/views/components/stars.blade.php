@props(['rating' => 0, 'size' => 'size-4'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} aria-label="{{ number_format($rating, 1) }} trên 5 sao">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="{{ $size }} {{ $rating >= $i - 0.25 ? 'text-accent-400' : ($rating >= $i - 0.75 ? 'text-accent-300' : 'text-gray-200') }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z"/>
        </svg>
    @endfor
</span>
