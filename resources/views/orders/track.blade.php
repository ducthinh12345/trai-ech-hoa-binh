<x-layouts.shop :seo="$seo">
    <div class="container-x max-w-lg py-12 lg:py-20">
        <div class="text-center">
            <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-100 text-brand-700"><x-heroicon-o-truck class="size-8" /></div>
            <h1 class="section-title mt-5">Tra cứu đơn hàng</h1>
            <p class="mt-2 text-gray-500">Nhập mã đơn (trong SMS/email xác nhận) và số điện thoại đặt hàng.</p>
        </div>
        <form action="{{ route('orders.track.submit') }}" method="POST" class="card mt-8 space-y-4 p-6">
            @csrf
            <div>
                <label for="code" class="label">Mã đơn hàng</label>
                <input id="code" name="code" value="{{ old('code') }}" required placeholder="TE-261010-ABCD" class="input uppercase placeholder:normal-case">
                @error('code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="label">Số điện thoại</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required placeholder="0912345678" class="input">
                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary btn-lg w-full">Tra cứu</button>
        </form>
    </div>
</x-layouts.shop>
