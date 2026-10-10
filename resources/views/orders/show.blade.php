<x-layouts.shop :seo="$seo">
    @php
        $steps = [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Confirmed, \App\Enums\OrderStatus::Processing, \App\Enums\OrderStatus::Shipping, \App\Enums\OrderStatus::Completed];
        $current = $order->status->stepIndex();
        $justPlaced = $order->created_at->gt(now()->subMinutes(30)) && $order->status === \App\Enums\OrderStatus::Pending;
    @endphp
    <div class="container-x max-w-5xl py-8 lg:py-12">
        @if ($justPlaced)
            <div class="card mb-8 overflow-hidden text-center">
                <div class="bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-10 text-white">
                    <div class="mx-auto grid size-16 place-items-center rounded-full bg-white/20"><x-heroicon-s-check class="size-9" /></div>
                    <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">Đặt hàng thành công!</h1>
                    <p class="mt-2 text-brand-100">Cảm ơn {{ $order->customer_name }}. Trại sẽ gọi xác nhận qua số <strong>{{ $order->customer_phone }}</strong> trong ít phút.</p>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Mã đơn hàng</p>
                <p class="text-2xl font-extrabold tracking-wide text-brand-900 sm:text-3xl">{{ $order->code }}</p>
                <p class="mt-1 text-sm text-gray-500">Đặt lúc {{ $order->created_at->format('H:i, d/m/Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="badge px-3 py-1.5 text-sm {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
                <span @class(['badge px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $order->isPaid(), 'bg-accent-100 text-accent-700' => ! $order->isPaid()])>{{ $order->payment_status->getLabel() }}</span>
            </div>
        </div>

        {{-- Tiến trình --}}
        @if (! $order->isCancelled())
            <div class="card mt-6 p-5 sm:p-7">
                <ol class="grid grid-cols-5 gap-1">
                    @foreach ($steps as $i => $step)
                        <li class="relative flex flex-col items-center text-center">
                            @if ($i > 0)<span @class(['absolute top-5 right-1/2 -z-0 h-1 w-full rounded', 'bg-brand-500' => $i <= $current, 'bg-gray-200' => $i > $current])></span>@endif
                            <span @class(['relative z-10 grid size-10 place-items-center rounded-full ring-4 ring-white transition', 'bg-brand-600 text-white' => $i <= $current, 'bg-gray-100 text-gray-400' => $i > $current, 'scale-110 shadow-lg' => $i === $current])>
                                <x-dynamic-component :component="'heroicon-o-'.str($step->getIcon()->value)->after('o-')" class="size-5" />
                            </span>
                            <span @class(['mt-2 text-[11px] leading-tight font-semibold sm:text-xs', 'text-brand-800' => $i <= $current, 'text-gray-400' => $i > $current])>{{ $step->getLabel() }}</span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-5 rounded-xl bg-brand-50 px-4 py-3 text-center text-sm text-brand-800">{{ $order->status->customerDescription() }} @if ($order->tracking_code) Mã vận đơn: <strong>{{ $order->tracking_code }}</strong> @endif</p>
            </div>
        @else
            <div class="mt-6 rounded-2xl bg-red-50 p-5 text-sm text-red-800 ring-1 ring-red-200">Đơn hàng đã huỷ lúc {{ $order->cancelled_at?->format('H:i d/m/Y') }}. @if ($order->cancel_reason) Lý do: {{ $order->cancel_reason }} @endif</div>
        @endif

        {{-- Thanh toán chuyển khoản / thanh toán lại --}}
        @if ($qrUrl)
            <div class="card mt-6 grid items-center gap-6 p-5 sm:p-7 md:grid-cols-[auto_1fr]">
                <img src="{{ $qrUrl }}" alt="Mã VietQR thanh toán đơn {{ $order->code }}" class="mx-auto w-56 rounded-xl ring-1 ring-brand-900/10" width="224" height="224">
                <div>
                    <h2 class="text-lg font-bold">Quét mã để chuyển khoản</h2>
                    <p class="mt-1 text-sm text-gray-500">Mở app ngân hàng bất kỳ → Quét QR. Số tiền và nội dung đã được điền sẵn.</p>
                    <dl class="mt-4 grid gap-2 text-sm" x-data>
                        @foreach ([['Ngân hàng', setting('bank_name')], ['Số tài khoản', setting('bank_account_number')], ['Chủ tài khoản', setting('bank_account_name')], ['Số tiền', money($order->total)], ['Nội dung', $order->code]] as [$label, $value])
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-brand-50/60 px-3 py-2">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="flex items-center gap-2 font-bold">{{ $value }}
                                    @if (in_array($label, ['Số tài khoản', 'Nội dung']))<button type="button" @click="navigator.clipboard.writeText(@js($value)); $dispatch('toast', { message: 'Đã sao chép' })" class="text-brand-600 hover:text-brand-800" aria-label="Sao chép"><x-heroicon-o-clipboard-document class="size-4" /></button>@endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        @elseif ($order->canRetryPayment())
            <div class="card mt-6 flex flex-col items-center justify-between gap-4 p-5 sm:flex-row sm:p-6">
                <div><h2 class="font-bold">Đơn hàng chưa được thanh toán</h2><p class="text-sm text-gray-500">Thanh toán qua {{ $order->payment_method->shortLabel() }} để trại xử lý đơn nhanh hơn.</p></div>
                <form action="{{ route('orders.pay', $order) }}" method="POST">@csrf<button class="btn-primary btn-lg">Thanh toán lại · {{ money($order->total) }}</button></form>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-5">
            <div class="card p-5 sm:p-6 lg:col-span-3">
                <h2 class="font-bold">Sản phẩm</h2>
                <ul class="mt-4 divide-y divide-brand-900/5">
                    @foreach ($order->items as $item)
                        <li class="flex gap-4 py-3">
                            <img src="{{ $item->imageUrl() }}" alt="" class="size-16 rounded-xl object-cover" width="64" height="64">
                            <div class="flex-1"><p class="font-semibold">{{ $item->product_name }}</p><p class="text-sm text-gray-500">{{ $item->variant_name ?? $item->unit }} · {{ money($item->unit_price) }} × {{ $item->quantity }}</p></div>
                            <span class="font-bold">{{ money($item->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-2 border-t border-brand-900/5 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Tạm tính</dt><dd>{{ money($order->subtotal) }}</dd></div>
                    @if ($order->discount_amount)<div class="flex justify-between text-brand-700"><dt>Giảm giá ({{ $order->coupon_code }})</dt><dd>−{{ money($order->discount_amount) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-gray-500">Vận chuyển ({{ $order->shipping_method_name }})</dt><dd>{{ $order->shipping_fee ? money($order->shipping_fee) : 'Miễn phí' }}</dd></div>
                    <div class="flex justify-between border-t border-brand-900/5 pt-3 text-base"><dt class="font-bold">Tổng cộng</dt><dd class="text-xl font-extrabold text-brand-700">{{ money($order->total) }}</dd></div>
                </dl>
            </div>
            <div class="space-y-6 lg:col-span-2">
                <div class="card p-5 sm:p-6 text-sm">
                    <h2 class="font-bold">Giao đến</h2>
                    <p class="mt-3 font-semibold">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                    <p class="mt-1 text-gray-600">{{ $order->fullAddress() }}</p>
                    @if ($order->customer_note)<p class="mt-3 rounded-lg bg-accent-50 p-3 text-gray-700">📝 {{ $order->customer_note }}</p>@endif
                    <p class="mt-4 border-t border-brand-900/5 pt-3"><span class="text-gray-500">Thanh toán:</span> <strong>{{ $order->payment_method->getLabel() }}</strong></p>
                </div>
                <div class="card p-5 sm:p-6">
                    <h2 class="font-bold">Lịch sử đơn hàng</h2>
                    <ol class="mt-4 space-y-4 border-l-2 border-brand-100 pl-5">
                        @foreach ($order->statusHistories as $h)
                            <li class="relative text-sm">
                                <span class="absolute top-1 -left-[27px] size-3 rounded-full bg-brand-500 ring-4 ring-white"></span>
                                <p class="font-semibold">{{ $h->to_status->getLabel() }}</p>
                                <p class="text-xs text-gray-500">{{ $h->created_at->format('H:i d/m/Y') }}</p>
                                @if ($h->note)<p class="mt-0.5 text-gray-600">{{ $h->note }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </div>
                @if ($order->canBeCancelledByCustomer())
                    <form action="{{ route('orders.cancel', $order) }}" method="POST" x-data="{ open: false }" class="text-center">
                        @csrf
                        <button type="button" @click="open = !open" class="text-sm font-semibold text-red-500 hover:underline">Huỷ đơn hàng</button>
                        <div x-show="open" x-collapse class="mt-3 space-y-2">
                            <input type="text" name="reason" placeholder="Lý do huỷ (không bắt buộc)" class="input">
                            <button class="btn w-full bg-red-500 text-white hover:bg-red-600">Xác nhận huỷ</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        <div class="mt-8 text-center"><a href="{{ route('products.index') }}" class="btn-outline">Tiếp tục mua sắm</a></div>
    </div>
</x-layouts.shop>
