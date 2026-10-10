<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Vòng đời đơn hàng. Thứ tự case cũng là thứ tự tiến trình bình thường;
 * Cancelled là nhánh thoát duy nhất và chỉ cho phép khi chưa giao.
 */
enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipping = 'shipping';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Confirmed => 'Đã xác nhận',
            self::Processing => 'Đang chuẩn bị',
            self::Shipping => 'Đang giao',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã huỷ',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Processing => 'primary',
            self::Shipping => 'violet',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Confirmed => Heroicon::OutlinedCheck,
            self::Processing => Heroicon::OutlinedCube,
            self::Shipping => Heroicon::OutlinedTruck,
            self::Completed => Heroicon::OutlinedCheckBadge,
            self::Cancelled => Heroicon::OutlinedXCircle,
        };
    }

    /**
     * Class Tailwind cho nhãn trạng thái ở storefront (frontend không nạp CSS của Filament).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Confirmed => 'bg-sky-100 text-sky-800',
            self::Processing => 'bg-brand-100 text-brand-800',
            self::Shipping => 'bg-violet-100 text-violet-800',
            self::Completed => 'bg-emerald-600 text-white',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }

    /**
     * Mô tả ngắn hiển thị cho khách ở trang theo dõi đơn.
     */
    public function customerDescription(): string
    {
        return match ($this) {
            self::Pending => 'Chúng tôi đã nhận đơn và sẽ gọi xác nhận trong ít phút.',
            self::Confirmed => 'Đơn đã được xác nhận, trại đang chuẩn bị hàng.',
            self::Processing => 'Hàng đang được đóng gói, giữ lạnh/oxy theo quy chuẩn.',
            self::Shipping => 'Đơn đang trên đường giao đến bạn.',
            self::Completed => 'Đơn đã giao thành công. Cảm ơn bạn!',
            self::Cancelled => 'Đơn đã bị huỷ.',
        };
    }

    /**
     * Các trạng thái được phép chuyển tới từ trạng thái hiện tại.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipping, self::Cancelled],
            self::Shipping => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Bước thứ mấy trên thanh tiến trình (0-based); Cancelled không nằm trên thanh.
     */
    public function stepIndex(): ?int
    {
        return match ($this) {
            self::Pending => 0,
            self::Confirmed => 1,
            self::Processing => 2,
            self::Shipping => 3,
            self::Completed => 4,
            self::Cancelled => null,
        };
    }
}
