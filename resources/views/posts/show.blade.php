<x-layouts.shop :seo="$seo">
    <article class="container-x max-w-3xl py-8 lg:py-12">
        <header>
            @if ($post->category)<a href="{{ $post->category->url() }}" class="section-eyebrow hover:underline">{{ $post->category->name }}</a>@endif
            <h1 class="mt-3 text-3xl leading-tight font-extrabold text-brand-950 text-balance sm:text-4xl lg:text-[2.75rem]">{{ $post->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                @if ($post->author)<span class="font-semibold text-ink">{{ $post->author->name }}</span><span>•</span>@endif
                <time datetime="{{ ($post->published_at ?? $post->created_at)->toIso8601String() }}">{{ ($post->published_at ?? $post->created_at)->format('d/m/Y') }}</time>
                <span>•</span><span>{{ $post->readingMinutes() }} phút đọc</span>
                <span>•</span><span>{{ number_format($post->views_count, 0, ',', '.') }} lượt xem</span>
            </div>
        </header>
        <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->title }}" class="mt-8 aspect-[16/9] w-full rounded-3xl object-cover" fetchpriority="high" width="1200" height="675">
        @if ($post->excerpt)<p class="mt-8 text-lg leading-8 font-medium text-ink/80">{{ $post->excerpt }}</p>@endif
        <div class="prose-content mt-6">{!! $post->content !!}</div>

        <div class="mt-12 flex flex-col items-center gap-4 rounded-3xl bg-brand-900 p-8 text-center text-white sm:flex-row sm:text-left">
            <div class="flex-1"><h2 class="text-xl font-bold">Cần tư vấn kỹ thuật nuôi ếch?</h2><p class="mt-1 text-sm text-brand-200">Kỹ sư của trại hỗ trợ miễn phí, từ chọn giống đến xuất bán.</p></div>
            <a href="tel:{{ preg_replace('/\D/', '', setting('store_phone', '')) }}" class="btn-accent btn-lg shrink-0"><x-heroicon-s-phone class="size-5" /> {{ setting('store_phone') }}</a>
        </div>
    </article>
    @if ($related->isNotEmpty())
        <section class="container-x pb-8">
            <x-section-heading title="Bài viết liên quan" :link="route('posts.index')" />
            <div class="grid gap-6 md:grid-cols-3">@foreach ($related as $item)<x-post-card :post="$item" />@endforeach</div>
        </section>
    @endif
</x-layouts.shop>
