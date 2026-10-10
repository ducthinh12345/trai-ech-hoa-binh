<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\OrderPlaced;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderConfirmationMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Báo đơn mới cho nhân viên (chuông thông báo trong admin + email nội bộ) và gửi email xác nhận cho khách.
 * Lỗi gửi mail chỉ ghi log, không được làm hỏng việc đặt hàng đã thành công.
 */
class SendOrderNotifications
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $staff = User::query()->whereIn('role', [UserRole::Admin, UserRole::Staff])->where('is_active', true)->get();
        Notification::make()
            ->title('Đơn hàng mới '.$order->code)
            ->body($order->customer_name.' · '.money($order->total).' · '.$order->payment_method->shortLabel())
            ->icon('heroicon-o-shopping-bag')
            ->success()
            ->actions([
                Action::make('view')->label('Xem đơn')->url(OrderResource::getUrl('view', ['record' => $order]))->markAsRead(),
            ])
            ->sendToDatabase($staff);

        try {
            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
            }

            $internal = array_filter(array_map('trim', explode(',', (string) setting('order_notify_emails', ''))));
            if ($internal !== []) {
                Mail::to($internal)->send(new OrderConfirmationMail($order));
            }
        } catch (Throwable $e) {
            Log::error('Gửi email đơn hàng thất bại', ['order' => $order->code, 'error' => $e->getMessage()]);
        }
    }
}
