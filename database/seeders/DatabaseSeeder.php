<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Mật khẩu admin lấy từ biến ADMIN_PASSWORD; mặc định "password" chỉ dành cho máy dev — đổi ngay khi lên server.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@traiechhoabinh.vn')], [
            'name' => 'Quản trị viên',
            'phone' => '0900000001',
            'password' => env('ADMIN_PASSWORD', 'password'),
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'nhanvien@traiechhoabinh.vn'], [
            'name' => 'Nhân viên bán hàng',
            'phone' => '0900000002',
            'password' => env('ADMIN_PASSWORD', 'password'),
            'role' => UserRole::Staff,
            'is_active' => true,
        ]);

        $this->call([
            SettingSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoOrderSeeder::class);
        }
    }
}
