<x-layouts.shop :seo="$seo">
    <div class="container-x max-w-md py-12 lg:py-20">
        <div class="text-center"><h1 class="section-title">Đăng nhập</h1><p class="mt-2 text-gray-500">Theo dõi đơn hàng và đặt hàng nhanh hơn.</p></div>
        <form action="{{ route('login.submit') }}" method="POST" class="card mt-8 space-y-4 p-6 sm:p-8">
            @csrf
            <div><label for="login" class="label">Email hoặc số điện thoại</label><input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="input">@error('login')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="password" class="label">Mật khẩu</label><input id="password" name="password" type="password" required autocomplete="current-password" class="input"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="size-4 rounded accent-brand-700"> Ghi nhớ đăng nhập</label>
            <button class="btn-primary btn-lg w-full">Đăng nhập</button>
            <p class="text-center text-sm text-gray-500">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Đăng ký ngay</a></p>
        </form>
    </div>
</x-layouts.shop>
