<x-account-layout :seo="$seo">
    <h1 class="text-xl font-bold">Sản phẩm yêu thích</h1>
    @if ($products->isEmpty())
        <div class="card mt-4 p-10 text-center text-gray-500">Chưa có sản phẩm yêu thích. Bấm ♡ ở trang sản phẩm để lưu lại.</div>
    @else
        <div class="mt-4 grid grid-cols-2 gap-4 xl:grid-cols-3">@foreach ($products as $product)<x-product-card :product="$product" />@endforeach</div>
    @endif
</x-account-layout>
