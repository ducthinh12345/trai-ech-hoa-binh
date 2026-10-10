<x-layouts.shop :seo="$seo">
    <div class="container-x max-w-md py-12 lg:py-20">
        <div class="text-center"><h1 class="section-title">Tạo tài khoản</h1><p class="mt-2 text-gray-500">Lưu địa chỉ, xem lịch sử mua và nhận ưu đãi thành viên.</p></div>
        <form action="{{ route('register.submit') }}" method="POST" class="card mt-8 space-y-4 p-6 sm:p-8">
            @csrf
            @foreach ([['name', 'Họ tên', 'text', 'name'], ['phone', 'Số điện thoại', 'tel', 'tel'], ['email', 'Email', 'email', 'email']] as [$f, $l, $t, $ac])
                <div><label for="{{ $f }}" class="label">{{ $l }}</label><input id="{{ $f }}" name="{{ $f }}" type="{{ $t }}" value="{{ old($f) }}" required autocomplete="{{ $ac }}" class="input">@error($f)<p class="field-error">{{ $message }}</p>@enderror</div>
            @endforeach
            <div><label for="password" class="label">Mật khẩu (tối thiểu 8 ký tự)</label><input id="password" name="password" type="password" required autocomplete="new-password" class="input">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="label">Nhập lại mật khẩu</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input"></div>
            <button class="btn-primary btn-lg w-full">Đăng ký</button>
            <p class="text-center text-sm text-gray-500">Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Đăng nhập</a></p>
        </form>
    </div>
</x-layouts.shop>
