<x-account-layout :seo="$seo">
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([['Đơn hàng', $stats['orders'], 'shopping-bag'], ['Tổng chi tiêu', money($stats['spent']), 'banknotes'], ['Yêu thích', $stats['wishlist'], 'heart']] as [$l, $v, $i])
            <div class="card flex items-center gap-4 p-5">
                <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-dynamic-component :component="'heroicon-o-'.$i" class="size-6" /></span>
                <div><p class="text-sm text-gray-500">{{ $l }}</p><p class="text-xl font-extrabold">{{ $v }}</p></div>
            </div>
        @endforeach
    </div>
    <h1 class="mt-8 text-xl font-bold">Đơn hàng của tôi</h1>
    <div class="mt-4 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('orders.show', $order) }}" class="card flex flex-wrap items-center gap-4 p-4 transition hover:shadow-[var(--shadow-lift)] sm:p-5">
                <div class="flex -space-x-3">
                    @foreach ($order->items->take(3) as $item)<img src="{{ $item->imageUrl() }}" alt="" class="size-12 rounded-xl object-cover ring-2 ring-white" width="48" height="48">@endforeach
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold">{{ $order->code }}</p>
                    <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y') }} · {{ $order->items->sum('quantity') }} sản phẩm</p>
                </div>
                <span class="badge {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
                <span class="w-28 text-right font-extrabold text-brand-700">{{ money($order->total) }}</span>
            </a>
        @empty
            <div class="card p-10 text-center"><p class="text-gray-500">Bạn chưa có đơn hàng nào.</p><a href="{{ route('products.index') }}" class="btn-primary mt-4">Mua sắm ngay</a></div>
        @endforelse
        {{ $orders->links() }}
    </div>
</x-account-layout>
