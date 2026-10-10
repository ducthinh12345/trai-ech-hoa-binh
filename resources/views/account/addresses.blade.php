<x-account-layout :seo="$seo">
    <h1 class="text-xl font-bold">Sổ địa chỉ</h1>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach ($addresses as $address)
            <div class="card relative p-5">
                @if ($address->is_default)<span class="badge absolute top-4 right-4 bg-brand-100 text-brand-800">Mặc định</span>@endif
                <p class="font-bold">{{ $address->recipient_name }}</p>
                <p class="text-sm text-gray-500">{{ $address->phone }}</p>
                <p class="mt-2 text-sm">{{ $address->fullAddress() }}</p>
                <form action="{{ route('account.addresses.destroy', $address) }}" method="POST" class="mt-3">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-500 hover:underline">Xoá</button></form>
            </div>
        @endforeach
    </div>
    <form action="{{ route('account.addresses.store') }}" method="POST" class="card mt-6 grid gap-4 p-6 sm:grid-cols-2">
        @csrf
        <h2 class="text-lg font-bold sm:col-span-2">Thêm địa chỉ mới</h2>
        <div><label class="label" for="recipient_name">Người nhận</label><input id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}" required class="input">@error('recipient_name')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="a-phone">Số điện thoại</label><input id="a-phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required class="input"></div>
        <div><label class="label" for="province_code">Tỉnh/Thành</label>
            <select id="province_code" name="province_code" required class="input"><option value="">— Chọn —</option>@foreach ($provinces as $c => $n)<option value="{{ $c }}" @selected(old('province_code') === $c)>{{ $n }}</option>@endforeach</select>
            @error('province_code')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="a-ward">Xã/Phường</label><input id="a-ward" name="ward" value="{{ old('ward') }}" required class="input"></div>
        <div class="sm:col-span-2"><label class="label" for="a-street">Số nhà, đường</label><input id="a-street" name="street" value="{{ old('street') }}" required class="input"></div>
        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_default" value="1" class="size-4 rounded accent-brand-700"> Đặt làm địa chỉ mặc định</label>
        <button class="btn-primary sm:col-span-2 sm:justify-self-start">Lưu địa chỉ</button>
    </form>
</x-account-layout>
