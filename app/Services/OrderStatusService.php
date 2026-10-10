<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Điểm vào duy nhất để đổi trạng thái đơn (admin, khách tự huỷ, callback thanh toán)
 * để lịch sử, mốc thời gian và hoàn kho luôn nhất quán.
 */
class OrderStatusService
{
    public function transition(Order $order, OrderStatus $to, ?string $note = null, ?int $userId = null): Order
    {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException("Không thể chuyển đơn từ \"{$from->getLabel()}\" sang \"{$to->getLabel()}\".");
        }

        DB::transaction(function () use ($order, $from, $to, $note, $userId): void {
            $order->status = $to;

            match ($to) {
                OrderStatus::Confirmed => $order->confirmed_at = now(),
                OrderStatus::Shipping => $order->shipped_at = now(),
                OrderStatus::Completed => $this->complete($order),
                OrderStatus::Cancelled => $this->cancel($order, $note),
                default => null,
            };

            $order->save();

            $order->statusHistories()->create([
                'user_id' => $userId,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);
        });

        return $order;
    }

    private function complete(Order $order): void
    {
        $order->completed_at = now();

        /* COD: giao xong tức là đã thu tiền. */
        if ($order->payment_status === PaymentStatus::Unpaid) {
            $order->payment_status = PaymentStatus::Paid;
            $order->paid_at = now();
        }
    }

    /**
     * Huỷ đơn thì trả hàng về kho và trả lượt dùng coupon.
     */
    private function cancel(Order $order, ?string $reason): void
    {
        $order->cancelled_at = now();
        $order->cancel_reason = $reason;

        foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
            if (! $item->product) {
                continue;
            }
            if ($item->product->track_stock) {
                if ($item->variant instanceof ProductVariant) {
                    $item->variant->increment('stock_quantity', $item->quantity);
                } else {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }
            $item->product->decrement('sold_count', min($item->quantity, $item->product->sold_count));
        }

        if ($order->coupon_code) {
            DB::table('coupons')->where('code', $order->coupon_code)->where('used_count', '>', 0)->decrement('used_count');
        }
    }
}
