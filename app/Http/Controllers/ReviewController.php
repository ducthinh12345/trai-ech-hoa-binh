<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Đánh giá luôn chờ duyệt để chặn spam; khách đã mua (theo SĐT/tài khoản) được gắn đơn để hiện nhãn "Đã mua hàng".
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:100'],
            'reviewer_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
            'website' => ['prohibited'],
        ], [], [
            'reviewer_name' => 'họ tên',
            'rating' => 'số sao',
            'content' => 'nội dung',
        ]);

        $user = $request->user();
        $orderId = $user
            ? Order::query()->where('user_id', $user->id)->whereHas('items', fn ($q) => $q->where('product_id', $product->id))->value('id')
            : null;

        $product->reviews()->create([
            'user_id' => $user?->id,
            'order_id' => $orderId,
            'reviewer_name' => $data['reviewer_name'],
            'reviewer_email' => $data['reviewer_email'] ?? $user?->email,
            'rating' => $data['rating'],
            'content' => $data['content'],
            'is_approved' => false,
        ]);

        return back()->with('success', 'Cảm ơn bạn đã đánh giá! Nội dung sẽ hiển thị sau khi được duyệt.')->withFragment('danh-gia');
    }
}
