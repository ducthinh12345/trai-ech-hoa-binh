@props(['post'])
<article {{ $attributes->merge(['class' => 'group card relative flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-[var(--shadow-lift)]']) }}>
    <div class="aspect-[16/10] overflow-hidden bg-brand-50">
        <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->title }}" loading="lazy" width="640" height="400" class="size-full object-cover transition duration-500 group-hover:scale-105">
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs text-gray-500">
            @if ($post->category)<span class="font-semibold text-brand-600">{{ $post->category->name }}</span><span>•</span>@endif
            <time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}">{{ ($post->published_at ?? $post->created_at)->format('d/m/Y') }}</time>
            <span>•</span><span>{{ $post->readingMinutes() }} phút đọc</span>
        </div>
        <h3 class="mt-2 line-clamp-2 text-lg leading-snug font-bold text-brand-950">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0 group-hover:text-brand-700">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)<p class="mt-2 line-clamp-3 text-sm leading-6 text-ink/70">{{ $post->excerpt }}</p>@endif
    </div>
</article>
