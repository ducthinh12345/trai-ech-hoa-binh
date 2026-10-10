<x-layouts.shop :seo="$seo">
    <div class="container-x py-8 lg:py-12">
        <div class="max-w-2xl">
            <p class="section-eyebrow">Blog</p>
            <h1 class="section-title mt-1.5">{{ $category?->name ?? 'Kiến thức nuôi ếch & món ngon' }}</h1>
            @if ($category?->description)<p class="mt-3 text-ink/70">{{ $category->description }}</p>@endif
        </div>
        <div class="mt-6 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
            <a href="{{ route('posts.index') }}" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-700 text-white' => ! $category, 'bg-white ring-1 ring-brand-900/10 hover:bg-brand-50' => $category])>Tất cả</a>
            @foreach ($categories as $cat)
                <a href="{{ $cat->url() }}" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-700 text-white' => $category?->id === $cat->id, 'bg-white ring-1 ring-brand-900/10 hover:bg-brand-50' => $category?->id !== $cat->id])>{{ $cat->name }} <span class="opacity-60">{{ $cat->posts_count }}</span></a>
            @endforeach
        </div>
        @if ($posts->isEmpty())
            <p class="card mt-8 p-10 text-center text-gray-500">Chưa có bài viết.</p>
        @else
            <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)<x-post-card :post="$post" />@endforeach
            </div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </div>
</x-layouts.shop>
