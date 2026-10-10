<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        $seo = Seo::make('Đăng nhập');
        $seo->noindex = true;

        return view('auth.login', compact('seo'));
    }

    /**
     * Cho phép đăng nhập bằng email hoặc số điện thoại vì nhiều khách nông nghiệp không dùng email.
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [], ['login' => 'email hoặc số điện thoại', 'password' => 'mật khẩu']);

        $throttleKey = Str::lower($data['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages(['login' => 'Bạn thử quá nhiều lần. Vui lòng đợi '.RateLimiter::availableIn($throttleKey).' giây.']);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $value = $field === 'phone' ? preg_replace('/\D/', '', $data['login']) : $data['login'];

        if (! Auth::attempt([$field => $value, 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages(['login' => 'Thông tin đăng nhập không đúng.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('account.index'));
    }

    public function showRegister(): View
    {
        $seo = Seo::make('Đăng ký tài khoản');
        $seo->noindex = true;

        return view('auth.register', compact('seo'));
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(0|84)[0-9]{9,10}$/', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['name' => 'họ tên', 'phone' => 'số điện thoại', 'password' => 'mật khẩu']);

        $user = User::create($data + ['role' => UserRole::Customer, 'is_active' => true]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('success', 'Chào mừng bạn đến với '.setting('store_name', config('app.name')).'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
