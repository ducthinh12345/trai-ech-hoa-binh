<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\CartException;
use App\Models\Order;
use App\Services\OrderStatusService;
use App\Services\Payment\PaymentManager;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trang xem đơn công khai. Quyền xem: chủ tài khoản, hoặc phiên vừa đặt đơn, hoặc nhập đúng SĐT ở trang tra cứu.
 * Không dùng mã đơn làm quyền duy nhất vì mã có thể bị lộ qua ảnh chụp màn hình.
 */
class OrderController extends Controller
{
    private const SESSION_KEY = 'orders.viewable';

    public static function grantAccess(Order $order): void
    {
        $codes = session()->get(self::SESSION_KEY, []);
        $codes[] = $order->code;
        session()->put(self::SESSION_KEY, array_values(array_unique(array_slice($codes, -20))));
    }

    public function show(Request $request, Order $order, PaymentManager $payments): View
    {
        $this->authorizeView($request, $order);

        $order->load(['items', 'statusHistories']);
        $seo = Seo::make('Đơn hàng '.$order->code);
        $seo->noindex = true;

        return view('orders.show', [
            'seo' => $seo,
            'order' => $order,
            'qrUrl' => $order->payment_method->value === 'bank_transfer' && ! $order->isPaid() ? $payments->vietQr()->imageUrl($order) : null,
        ]);
    }

    public function trackForm(): View
    {
        $seo = Seo::make('Tra cứu đơn hàng', 'Nhập mã đơn và số điện thoại để xem tình trạng đơn hàng của bạn.');

        return view('orders.track', compact('seo'));
    }

    public function track(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
        ], [], ['code' => 'mã đơn hàng', 'phone' => 'số điện thoại']);

        $order = Order::query()
            ->where('code', strtoupper(trim($data['code'])))
            ->where('customer_phone', preg_replace('/\D/', '', $data['phone']))
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors(['code' => 'Không tìm thấy đơn hàng khớp mã và số điện thoại.']);
        }

        self::grantAccess($order);

        return redirect()->route('orders.show', $order);
    }

    public function pay(Request $request, Order $order, PaymentManager $payments): RedirectResponse
    {
        $this->authorizeView($request, $order);

        if (! $order->canRetryPayment()) {
            return redirect()->route('orders.show', $order);
        }

        try {
            return redirect()->away($payments->initiate($order, (string) $request->ip()));
        } catch (CartException $e) {
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        $this->authorizeView($request, $order);

        if (! $order->canBeCancelledByCustomer()) {
            return back()->with('error', 'Đơn hàng đã được xử lý, vui lòng gọi hotline để được hỗ trợ huỷ.');
        }

        $reason = trim((string) $request->input('reason')) ?: 'Khách tự huỷ trên website';
        $statuses->transition($order, OrderStatus::Cancelled, str($reason)->limit(250)->toString(), $request->user()?->id);

        return back()->with('success', 'Đã huỷ đơn hàng.');
    }

    private function authorizeView(Request $request, Order $order): void
    {
        $allowed = in_array($order->code, session()->get(self::SESSION_KEY, []), true)
            || ($request->user() && $order->user_id === $request->user()->id)
            || ($request->user()?->role->canAccessAdminPanel() ?? false);

        abort_unless($allowed, 404);
    }
}
