<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Giá trị mặc định cho trang Cài đặt. Chỉ ghi khoá chưa tồn tại để chạy lại seeder không đè chỉnh sửa của admin.
 */
class SettingSeeder extends Seeder
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            'general' => [
                'store_name' => 'Trại Ếch Hòa Bình',
                'store_tagline' => 'Ếch sạch từ trại đến bàn ăn',
                'store_description' => 'Trại ếch giống và ếch thương phẩm tại Hòa Bình. Cung cấp ếch giống Thái Lan khoẻ mạnh, ếch thịt tươi sống và thịt ếch làm sạch giao tận nơi toàn quốc.',
                'store_phone' => '0912 345 678',
                'store_zalo' => '0912345678',
                'store_email' => 'lienhe@traiechhoabinh.vn',
                'store_address' => 'Xóm Đồng Chanh, Phường Hòa Bình, Tỉnh Phú Thọ',
                'store_working_hours' => '6:00 – 20:00, tất cả các ngày',
                'announcement_text' => '🚚 Miễn phí vận chuyển cho đơn từ 1.000.000đ — Giao ếch sống toàn quốc trong 24–48h',
                'business_license' => 'GPKD số 25A8xxxxxx do UBND tỉnh Phú Thọ cấp.',
                'free_shipping_threshold' => 1000000,
            ],
            'home' => [
                'about_title' => 'Hơn 10 năm gắn bó với nghề nuôi ếch',
                'about_text' => 'Từ vài ao đất nhỏ, trại đã phát triển thành cơ sở sản xuất ếch giống quy mô 3 ha với quy trình khép kín: chọn ếch bố mẹ, cho đẻ, ương nòng nọc đến nuôi thương phẩm. Mỗi lứa ếch rời trại đều được kiểm tra sức khoẻ và tập ăn cám trước khi giao.',
                'stat_years' => '10+',
                'stat_customers' => '5.000+',
                'stat_provinces' => '34',
            ],
            'social' => [
                'social_facebook' => 'https://facebook.com/',
                'social_youtube' => 'https://youtube.com/',
                'social_tiktok' => '',
                'store_map_embed' => '',
            ],
            'payment' => [
                'payment_cod_enabled' => true,
                'payment_bank_enabled' => true,
                'bank_name' => 'Vietcombank',
                'bank_bin' => '970436',
                'bank_account_number' => '0123456789',
                'bank_account_name' => 'TRAI ECH HOA BINH',
                'payment_vnpay_enabled' => false,
                'vnpay_tmn_code' => '',
                'vnpay_hash_secret' => '',
                'vnpay_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
                'payment_momo_enabled' => false,
                'momo_partner_code' => '',
                'momo_access_key' => '',
                'momo_secret_key' => '',
                'momo_endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            ],
            'seo' => [
                'seo_default_description' => 'Trại Ếch Hòa Bình chuyên cung cấp ếch giống Thái Lan, ếch thịt sống, thịt ếch làm sạch và thức ăn nuôi ếch. Giá tại trại, giao toàn quốc, hỗ trợ kỹ thuật miễn phí.',
                'seo_noindex_site' => false,
                'google_site_verification' => '',
                'google_analytics_id' => '',
                'facebook_pixel_id' => '',
                'custom_head_code' => '',
                'custom_body_code' => '',
            ],
            'notification' => [
                'order_notify_emails' => '',
            ],
        ];
    }

    public function run(): void
    {
        $existing = Setting::query()->pluck('key')->all();

        foreach (self::defaults() as $group => $values) {
            foreach ($values as $key => $value) {
                if (! in_array($key, $existing, true)) {
                    Setting::set($key, $value, $group);
                }
            }
        }

        Setting::flushCache();
    }
}
