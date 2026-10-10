<x-layouts.shop :seo="$seo">
    <article class="container-x max-w-3xl py-8 lg:py-12">
        <h1 class="section-title">{{ $page->title }}</h1>
        <div class="card prose-content mt-8 p-6 sm:p-10">{!! $page->content !!}</div>
    </article>
</x-layouts.shop>
