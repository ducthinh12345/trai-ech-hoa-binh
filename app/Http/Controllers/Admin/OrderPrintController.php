<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phiếu giao hàng/hoá đơn khổ A5 để in dán thùng; in nhiều đơn một lần (mỗi đơn một trang).
 */
class OrderPrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->is_active && $request->user()->role->canAccessAdminPanel(), 403);

        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
        $orders = Order::query()->with('items')->whereIn('id', $ids)->orderBy('id')->get();
        abort_if($orders->isEmpty(), 404);

        return view('admin.order-print', compact('orders'));
    }
}
