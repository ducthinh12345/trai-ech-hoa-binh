# Trại Ếch Hòa Bình — website bán nông sản

Laravel 13 · Filament 5 (trang quản trị) · Livewire 4 · Tailwind 4 · MySQL 8.

## Cài đặt máy dev

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# sửa DB_* trong .env, tạo database utf8mb4
php artisan migrate --seed      # tạo bảng + dữ liệu mẫu + tài khoản admin
php artisan storage:link
npm run build                   # hoặc: composer run dev
php artisan serve
```

- Website: http://localhost:8000
- Quản trị: http://localhost:8000/admin — tài khoản `ADMIN_EMAIL` / `ADMIN_PASSWORD` trong `.env`
  (để trống thì mật khẩu là `password`, chỉ dùng trên máy dev).
- Seeder `DemoOrderSeeder` (85 đơn giả cho biểu đồ) không chạy khi `APP_ENV=production`.

## Thanh toán online

Nhập khoá trong **Admin → Cài đặt cửa hàng → Thanh toán**. Phương thức chỉ hiện cho khách khi đã bật và đủ khoá.

| Cổng | URL cần khai báo với cổng |
|---|---|
| VNPay | IPN: `https://<domain>/payment/vnpay/ipn` · Return: tự gửi |
| MoMo | IPN và Return tự gửi kèm mỗi giao dịch |
| VietQR | Không cần đăng ký, chỉ nhập mã BIN + số tài khoản |

Return URL chỉ điều hướng khách; IPN là nguồn xác nhận. Cả hai đi qua `PaymentManager::handleCallback`, kiểm chữ ký,
so số tiền và idempotent. Server production cần HTTPS và URL công khai để cổng gọi được IPN.

## Lên production

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` đúng domain (dùng cho canonical, sitemap, URL thanh toán).
- `php artisan optimize` sau mỗi lần deploy; `QUEUE_CONNECTION=sync` đang dùng (email gửi ngay trong request).
- Cấu hình `MAIL_*` để gửi email xác nhận đơn.
- Gửi `https://<domain>/sitemap.xml` trong Google Search Console.

## Test

```bash
php artisan test --compact    # SQLite in-memory, không đụng DB dev
```
