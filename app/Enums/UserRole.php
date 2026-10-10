<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Admin và Staff vào được trang quản trị; Customer chỉ dùng tài khoản mua hàng.
 * Staff không được sửa cài đặt hệ thống và quản lý người dùng.
 */
enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Quản trị viên',
            self::Staff => 'Nhân viên',
            self::Customer => 'Khách hàng',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Staff => 'info',
            self::Customer => 'gray',
        };
    }

    public function canAccessAdminPanel(): bool
    {
        return $this !== self::Customer;
    }
}
