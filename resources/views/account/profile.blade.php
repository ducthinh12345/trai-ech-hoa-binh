<x-account-layout :seo="$seo">
    <div class="grid gap-6 xl:grid-cols-2">
        <form action="{{ route('account.profile.update') }}" method="POST" class="card space-y-4 p-6">
            @csrf @method('PUT')
            <h1 class="text-lg font-bold">Thông tin cá nhân</h1>
            @foreach ([['name', 'Họ tên', 'text'], ['phone', 'Số điện thoại', 'tel'], ['email', 'Email', 'email']] as [$f, $l, $t])
                <div><label for="{{ $f }}" class="label">{{ $l }}</label><input id="{{ $f }}" name="{{ $f }}" type="{{ $t }}" value="{{ old($f, $user->$f) }}" required class="input">@error($f)<p class="field-error">{{ $message }}</p>@enderror</div>
            @endforeach
            <button class="btn-primary">Lưu thay đổi</button>
        </form>
        <form action="{{ route('account.password.update') }}" method="POST" class="card space-y-4 p-6">
            @csrf @method('PUT')
            <h2 class="text-lg font-bold">Đổi mật khẩu</h2>
            <div><label for="current_password" class="label">Mật khẩu hiện tại</label><input id="current_password" name="current_password" type="password" required class="input">@error('current_password')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="password" class="label">Mật khẩu mới</label><input id="password" name="password" type="password" required class="input">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="label">Nhập lại mật khẩu mới</label><input id="password_confirmation" name="password_confirmation" type="password" required class="input"></div>
            <button class="btn-primary">Đổi mật khẩu</button>
        </form>
    </div>
</x-account-layout>
