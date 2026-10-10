<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderStatusService;
use App\Services\Payment\PaymentManager;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Đơn hàng '.$this->record->code;
    }

    public static function applyTransition(Order $order, OrderStatus $to, ?string $note = null, bool $notify = true): void
    {
        try {
            app(OrderStatusService::class)->transition($order, $to, $note, auth()->id());
            if ($notify) {
                Notification::make()->title('Đã chuyển sang "'.$to->getLabel().'"')->success()->send();
            }
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')->label('Xác nhận đơn')->icon('heroicon-o-check')->color('info')
                ->visible(fn () => $this->record->status->canTransitionTo(OrderStatus::Confirmed))
                ->requiresConfirmation()->modalDescription('Bạn đã gọi điện xác nhận với khách?')
                ->action(fn () => self::applyTransition($this->record, OrderStatus::Confirmed, 'Đã gọi xác nhận với khách')),

            Action::make('process')->label('Chuẩn bị hàng')->icon('heroicon-o-cube')->color('primary')
                ->visible(fn () => $this->record->status->canTransitionTo(OrderStatus::Processing))
                ->action(fn () => self::applyTransition($this->record, OrderStatus::Processing)),

            Action::make('ship')->label('Giao hàng')->icon('heroicon-o-truck')->color('violet')
                ->visible(fn () => $this->record->status->canTransitionTo(OrderStatus::Shipping))
                ->schema([
                    TextInput::make('tracking_code')->label('Mã vận đơn / biển số xe')->helperText('Không bắt buộc. Khách sẽ thấy mã này khi tra cứu đơn.'),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['tracking_code' => $data['tracking_code'] ?? null]);
                    self::applyTransition($this->record, OrderStatus::Shipping, $data['tracking_code'] ? 'Mã vận đơn: '.$data['tracking_code'] : null);
                }),

            Action::make('complete')->label('Hoàn thành')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => $this->record->status->canTransitionTo(OrderStatus::Completed))
                ->requiresConfirmation()
                ->modalDescription(fn () => $this->record->isPaid() ? 'Khách đã nhận hàng?' : 'Khách đã nhận hàng và trả tiền? Đơn sẽ được đánh dấu đã thanh toán.')
                ->action(fn () => self::applyTransition($this->record, OrderStatus::Completed, 'Giao thành công')),

            Action::make('mark_paid')->label('Đã nhận tiền')->icon('heroicon-o-banknotes')->color('success')->outlined()
                ->visible(fn () => ! $this->record->isPaid() && ! $this->record->isCancelled() && $this->record->payment_method !== PaymentMethod::Cod)
                ->requiresConfirmation()
                ->modalHeading('Xác nhận đã nhận chuyển khoản?')
                ->modalDescription(fn () => 'Kiểm tra tài khoản ngân hàng đã nhận '.money($this->record->total).' với nội dung "'.$this->record->code.'".')
                ->action(function (): void {
                    app(PaymentManager::class)->markPaid($this->record, 'Xác nhận đã nhận chuyển khoản', auth()->id());
                    Notification::make()->title('Đã ghi nhận thanh toán')->success()->send();
                }),

            ActionGroup::make([
                Action::make('note')->label('Ghi chú nội bộ')->icon('heroicon-o-pencil-square')
                    ->fillForm(fn () => ['admin_note' => $this->record->admin_note])
                    ->schema([Textarea::make('admin_note')->label('Ghi chú (khách không thấy)')->rows(4)])
                    ->action(fn (array $data) => $this->record->update($data))
                    ->successNotificationTitle('Đã lưu ghi chú'),
                Action::make('print')->label('In hoá đơn')->icon('heroicon-o-printer')
                    ->url(fn () => route('admin.orders.print', ['ids' => $this->record->id]))->openUrlInNewTab(),
                Action::make('customer_view')->label('Xem như khách')->icon('heroicon-o-eye')
                    ->url(fn () => route('orders.show', $this->record))->openUrlInNewTab(),
                Action::make('cancel')->label('Huỷ đơn')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn () => $this->record->status->canTransitionTo(OrderStatus::Cancelled))
                    ->schema([Textarea::make('reason')->label('Lý do huỷ')->required()->rows(2)])
                    ->modalDescription('Hàng sẽ được hoàn lại kho và trả lượt dùng mã giảm giá.')
                    ->action(fn (array $data) => self::applyTransition($this->record, OrderStatus::Cancelled, $data['reason'])),
            ])->label('Thêm')->icon('heroicon-m-ellipsis-vertical')->button()->color('gray'),
        ];
    }
}
