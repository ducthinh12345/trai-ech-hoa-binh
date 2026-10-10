<div class="container-x py-8 lg:py-12">
    <div class="flex items-center gap-3">
        <a href="{{ route('cart.index') }}" class="grid size-10 place-items-center rounded-full bg-white ring-1 ring-brand-900/10 hover:bg-brand-50" aria-label="Quay lại giỏ hàng"><x-heroicon-m-arrow-left class="size-5" /></a>
        <h1 class="section-title">Đặt hàng</h1>
    </div>

    <form wire:submit="placeOrder" class="mt-8 grid gap-8 lg:grid-cols-12">
        <div class="space-y-6 lg:col-span-7">
            @error('order')
                <div class="rounded-2xl bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-red-200">{{ $message }}</div>
            @enderror

            {{-- 1. Người nhận --}}
            <section class="card p-5 sm:p-6">
                <h2 class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-700 text-sm text-white">1</span>Thông tin người nhận</h2>

                @if ($addresses->isNotEmpty())
                    <div class="mt-4 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
                        @foreach ($addresses as $addr)
                            <button type="button" wire:click="useAddress({{ $addr->id }})" @class(['shrink-0 rounded-xl border-2 px-3 py-2 text-left text-xs transition', 'border-brand-600 bg-brand-50' => $savedAddressId === $addr->id, 'border-brand-900/10 hover:border-brand-300' => $savedAddressId !== $addr->id])>
                                <span class="block font-bold">{{ $addr->recipient_name }}</span>
                                <span class="block max-w-[14rem] truncate text-gray-500">{{ $addr->fullAddress() }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif (! auth()->check())
                    <p class="mt-2 text-sm text-gray-500">Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Đăng nhập</a> để điền nhanh. Không cần tài khoản vẫn đặt được hàng.</p>
                @endif

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="customerName" class="label">Họ và tên <span class="text-red-500">*</span></label>
                        <input id="customerName" type="text" wire:model.blur="customerName" autocomplete="name" class="input" placeholder="Nguyễn Văn A">
                        @error('customerName')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="customerPhone" class="label">Số điện thoại <span class="text-red-500">*</span></label>
                        <input id="customerPhone" type="tel" wire:model.blur="customerPhone" autocomplete="tel" inputmode="tel" class="input" placeholder="0912 345 678">
                        @error('customerPhone')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="customerEmail" class="label">Email <span class="font-normal text-gray-400">(không bắt buộc)</span></label>
                        <input id="customerEmail" type="email" wire:model.blur="customerEmail" autocomplete="email" class="input" placeholder="Nhận xác nhận đơn qua email">
                        @error('customerEmail')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="provinceCode" class="label">Tỉnh / Thành phố <span class="text-red-500">*</span></label>
                        <select id="provinceCode" wire:model.live="provinceCode" class="input">
                            <option value="">— Chọn tỉnh/thành —</option>
                            @foreach ($provinces as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('provinceCode')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ward" class="label">Xã / Phường <span class="text-red-500">*</span></label>
                        <input id="ward" type="text" wire:model.blur="ward" class="input" placeholder="VD: Phường Hòa Bình">
                        @error('ward')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="street" class="label">Số nhà, tên đường / thôn, xóm <span class="text-red-500">*</span></label>
                        <input id="street" type="text" wire:model.blur="street" autocomplete="street-address" class="input" placeholder="VD: Số 12, đường Cù Chính Lan">
                        @error('street')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="note" class="label">Ghi chú cho trại <span class="font-normal text-gray-400">(không bắt buộc)</span></label>
                        <textarea id="note" wire:model.blur="note" rows="2" class="input" placeholder="VD: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                    </div>
                </div>
            </section>

            {{-- 2. Vận chuyển --}}
            <section class="card p-5 sm:p-6">
                <h2 class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-700 text-sm text-white">2</span>Phương thức vận chuyển</h2>
                @if (! $provinceCode)
                    <p class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">Chọn tỉnh/thành để xem phí vận chuyển.</p>
                @elseif ($shippingMethods->isEmpty())
                    <p class="mt-4 rounded-xl bg-accent-50 px-4 py-3 text-sm text-accent-700">Chưa có phương thức giao tới khu vực này. Vui lòng gọi hotline để được hỗ trợ.</p>
                @else
                    <div class="mt-4 space-y-2.5">
                        @foreach ($shippingMethods as $method)
                            @php $fee = $shippingCalculator->fee($method, $subtotal, $coupon); @endphp
                            <label wire:key="ship-{{ $method->id }}" @class(['flex cursor-pointer items-center gap-4 rounded-xl border-2 p-4 transition', 'border-brand-600 bg-brand-50/60' => $shippingMethodId === $method->id, 'border-brand-900/10 hover:border-brand-300' => $shippingMethodId !== $method->id])>
                                <input type="radio" wire:model.live="shippingMethodId" value="{{ $method->id }}" class="size-4.5 accent-brand-700">
                                <div class="flex-1">
                                    <p class="font-semibold">{{ $method->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $method->description }} @if ($method->estimated_days) · {{ $method->estimated_days }} @endif</p>
                                </div>
                                <span @class(['font-bold', 'text-brand-600' => $fee === 0])>{{ $fee === 0 ? 'Miễn phí' : money($fee) }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('shippingMethodId')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            {{-- 3. Thanh toán --}}
            <section class="card p-5 sm:p-6">
                <h2 class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-700 text-sm text-white">3</span>Phương thức thanh toán</h2>
                <div class="mt-4 space-y-2.5">
                    @forelse ($paymentMethods as $pm)
                        <label wire:key="pm-{{ $pm->value }}" @class(['flex cursor-pointer items-start gap-4 rounded-xl border-2 p-4 transition', 'border-brand-600 bg-brand-50/60' => $paymentMethod === $pm->value, 'border-brand-900/10 hover:border-brand-300' => $paymentMethod !== $pm->value])>
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $pm->value }}" class="mt-1 size-4.5 accent-brand-700">
                            <div class="flex-1">
                                <p class="font-semibold">{{ $pm->getLabel() }}</p>
                                <p class="text-xs text-gray-500">{{ $pm->description() }}</p>
                            </div>
                            <span @class([
                                'rounded-lg px-2 py-1 text-[11px] font-extrabold',
                                'bg-brand-100 text-brand-800' => $pm->value === 'cod',
                                'bg-sky-100 text-sky-800' => $pm->value === 'bank_transfer',
                                'bg-blue-600 text-white' => $pm->value === 'vnpay',
                                'bg-pink-600 text-white' => $pm->value === 'momo',
                            ])>{{ $pm->shortLabel() }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-red-600">Cửa hàng chưa bật phương thức thanh toán nào.</p>
                    @endforelse
                </div>
                @error('paymentMethod')<p class="field-error">{{ $message }}</p>@enderror
            </section>
        </div>

        {{-- Tóm tắt --}}
        <aside class="lg:col-span-5">
            <div class="card sticky top-36 p-5 sm:p-6">
                <h2 class="text-lg font-bold">Đơn hàng ({{ $items->sum('quantity') }} sản phẩm)</h2>
                <ul class="mt-4 max-h-72 space-y-3 overflow-y-auto pr-1">
                    @foreach ($items as $item)
                        <li class="flex gap-3">
                            <div class="relative shrink-0">
                                <img src="{{ $item->imageUrl() }}" alt="" class="size-14 rounded-lg object-cover" width="56" height="56">
                                <span class="absolute -top-1.5 -right-1.5 grid size-5 place-items-center rounded-full bg-brand-800 text-[10px] font-bold text-white">{{ $item->quantity }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-1 text-sm font-semibold">{{ $item->name() }}</p>
                                @if ($item->variantName())<p class="text-xs text-gray-500">{{ $item->variantName() }}</p>@endif
                            </div>
                            <span class="text-sm font-semibold">{{ money($item->lineTotal()) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-5 space-y-2.5 border-t border-brand-900/5 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Tạm tính</dt><dd class="font-semibold">{{ money($subtotal) }}</dd></div>
                    @if ($discount > 0)<div class="flex justify-between text-brand-700"><dt>Giảm giá ({{ $coupon->code }})</dt><dd class="font-semibold">−{{ money($discount) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-gray-500">Vận chuyển</dt><dd class="font-semibold">{{ $shippingFee === null ? '—' : ($shippingFee === 0 ? 'Miễn phí' : money($shippingFee)) }}</dd></div>
                </dl>
                <div class="mt-4 flex items-end justify-between border-t border-brand-900/5 pt-4">
                    <span class="font-bold">Tổng thanh toán</span>
                    <span class="text-3xl font-extrabold text-brand-700">{{ money($total) }}</span>
                </div>
                <label class="mt-5 flex items-start gap-2.5 text-xs text-gray-600">
                    <input type="checkbox" wire:model="agree" class="mt-0.5 size-4 rounded accent-brand-700">
                    <span>Tôi đồng ý với chính sách mua hàng và đổi trả của cửa hàng.</span>
                </label>
                @error('agree')<p class="field-error">{{ $message }}</p>@enderror
                <button type="submit" class="btn-primary btn-lg mt-4 w-full" wire:loading.attr="disabled" wire:target="placeOrder">
                    <span wire:loading.remove wire:target="placeOrder">Đặt hàng · {{ money($total) }}</span>
                    <span wire:loading wire:target="placeOrder" class="flex items-center gap-2">
                        <svg class="size-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Đang tạo đơn...
                    </span>
                </button>
                <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-gray-500"><x-heroicon-o-lock-closed class="size-3.5" />Thông tin được mã hoá và bảo mật</p>
            </div>
        </aside>
    </form>
</div>
