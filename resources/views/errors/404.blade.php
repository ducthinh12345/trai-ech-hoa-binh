<x-layouts.shop :seo="tap(\App\Support\Seo::make('Không tìm thấy trang'), fn ($s) => $s->noindex = true)">
    <div class="container-x flex flex-col items-center py-20 text-center lg:py-28">
        <p class="text-8xl font-extrabold text-brand-200">404</p>
        <h1 class="mt-4 text-2xl font-extrabold text-brand-950">Chú ếch nhảy mất trang này rồi 🐸</h1>
        <p class="mt-2 max-w-md text-gray-500">Trang bạn tìm không tồn tại hoặc đã được chuyển. Thử tìm sản phẩm bên dưới nhé.</p>
        <form action="{{ route('products.index') }}" class="mt-6 flex w-full max-w-md gap-2"><input name="q" class="input rounded-full" placeholder="Tìm sản phẩm..."><button class="btn-primary">Tìm</button></form>
        <a href="{{ route('home') }}" class="btn-ghost mt-4">Về trang chủ</a>
    </div>
</x-layouts.shop>
