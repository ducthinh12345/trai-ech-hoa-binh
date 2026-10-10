<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Product;
use App\Support\Provinces;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('account.index', [
            'seo' => $this->seo('Tài khoản của tôi'),
            'orders' => $user->orders()->with('items')->latest()->paginate(10),
            'stats' => [
                'orders' => $user->orders()->count(),
                'spent' => (int) $user->orders()->revenue()->sum('total'),
                'wishlist' => $user->wishlistProducts()->count(),
            ],
        ]);
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['seo' => $this->seo('Thông tin cá nhân'), 'user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(0|84)[0-9]{9,10}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ], [], ['name' => 'họ tên', 'phone' => 'số điện thoại']);

        $user->update($data);

        return back()->with('success', 'Đã cập nhật thông tin.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['current_password' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới']);

        $request->user()->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', 'Đã đổi mật khẩu.');
    }

    public function addresses(Request $request): View
    {
        return view('account.addresses', [
            'seo' => $this->seo('Sổ địa chỉ'),
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->get(),
            'provinces' => Provinces::options(),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'province_code' => ['required', Rule::in(array_keys(Provinces::options()))],
            'ward' => ['required', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ], [], ['recipient_name' => 'người nhận', 'province_code' => 'tỉnh/thành', 'ward' => 'xã/phường', 'street' => 'địa chỉ']);

        $user = $request->user();
        $isDefault = $request->boolean('is_default') || ! $user->addresses()->exists();
        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create($data + [
            'province_name' => Provinces::name($data['province_code']),
            'is_default' => $isDefault,
            'phone' => preg_replace('/\D/', '', $data['phone']),
        ]);

        return back()->with('success', 'Đã thêm địa chỉ.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return back()->with('success', 'Đã xoá địa chỉ.');
    }

    public function wishlist(Request $request): View
    {
        return view('account.wishlist', [
            'seo' => $this->seo('Sản phẩm yêu thích'),
            'products' => $request->user()->wishlistProducts()->with('variants')->latest('wishlists.created_at')->get(),
        ]);
    }

    public function toggleWishlist(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlistProducts()->toggle($product->id);

        return back();
    }

    private function seo(string $title): Seo
    {
        $seo = Seo::make($title);
        $seo->noindex = true;

        return $seo;
    }
}
