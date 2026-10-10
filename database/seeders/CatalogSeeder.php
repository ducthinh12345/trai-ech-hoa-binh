<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingMethod;
use Database\Seeders\Support\DemoArt;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'ech-giong' => ['Ếch giống', 'Ếch giống Thái Lan, ếch lai F1 đã qua giai đoạn nòng nọc, khoẻ mạnh, đồng đều, đã tập ăn cám viên. Phù hợp nuôi bể bạt, ao đất, lồng lưới.', 'green', 'frog'],
            'ech-thit-song' => ['Ếch thịt sống', 'Ếch thương phẩm nuôi 3–4 tháng, size 4–8 con/kg, giao sống bằng thùng có lỗ thoáng khí, cam kết tỷ lệ sống.', 'teal', 'frog'],
            'thit-ech-lam-sach' => ['Thịt ếch làm sạch', 'Ếch làm sạch trong ngày: bỏ da, bỏ nội tạng, cấp đông nhanh giữ độ ngọt. Tiện lợi cho bữa cơm gia đình và nhà hàng.', 'rose', 'legs'],
            'che-bien-san' => ['Chế biến sẵn', 'Chả ếch, ếch tẩm ướp sẵn theo công thức của trại — chỉ cần chiên, nướng là có món ngon.', 'amber', 'cake'],
            'thuc-an-vat-tu' => ['Thức ăn & vật tư', 'Cám viên chuyên dụng cho ếch theo từng giai đoạn, men vi sinh, lưới, bạt nuôi ếch.', 'stone', 'bag'],
        ];

        $catModels = [];
        $sort = 0;
        foreach ($categories as $slug => [$name, $desc, $palette, $motif]) {
            $catModels[$slug] = Category::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $desc,
                'image' => DemoArt::product("categories/{$slug}.svg", '', $palette, $motif),
                'sort_order' => $sort++,
                'is_active' => true,
                'show_in_menu' => true,
                'seo_title' => "{$name} giá tại trại – giao toàn quốc",
                'seo_description' => $desc,
            ]);
        }

        $catModels['ech-giong-thai-lan'] = Category::updateOrCreate(['slug' => 'ech-giong-thai-lan'], [
            'parent_id' => $catModels['ech-giong']->id, 'name' => 'Ếch giống Thái Lan', 'sort_order' => 0, 'is_active' => true, 'show_in_menu' => true,
            'description' => 'Giống ếch Thái Lan lớn nhanh, thịt chắc, tỷ lệ hao hụt thấp.',
        ]);
        $catModels['nong-noc'] = Category::updateOrCreate(['slug' => 'nong-noc'], [
            'parent_id' => $catModels['ech-giong']->id, 'name' => 'Nòng nọc', 'sort_order' => 1, 'is_active' => true, 'show_in_menu' => true,
            'description' => 'Nòng nọc 7–10 ngày tuổi cho hộ có kinh nghiệm ương.',
        ]);

        foreach ($this->products() as $i => $data) {
            $variants = $data['variants'] ?? [];
            $slug = str($data['name'])->slug()->toString();
            $images = [];
            foreach (range(0, ($data['image_count'] ?? 2) - 1) as $n) {
                $images[] = DemoArt::product("products/{$slug}-{$n}.svg", $data['label'] ?? $data['name'], $data['palette'], $data['motif'], $n);
            }

            $product = Product::updateOrCreate(['slug' => $slug], [
                'category_id' => $catModels[$data['category']]->id,
                'name' => $data['name'],
                'sku' => $data['sku'],
                'short_description' => $data['short'],
                'description' => $data['description'],
                'images' => $images,
                'price' => $data['price'],
                'compare_at_price' => $data['compare'] ?? null,
                'unit' => $data['unit'],
                'min_order_quantity' => $data['min'] ?? 1,
                'track_stock' => true,
                'stock_quantity' => $data['stock'],
                'low_stock_threshold' => $data['low'] ?? 5,
                'specifications' => $data['specs'],
                'is_active' => true,
                'is_featured' => $data['featured'] ?? false,
                'sort_order' => $i,
                'sold_count' => $data['sold'] ?? 0,
            ]);

            $product->variants()->delete();
            foreach ($variants as $j => [$name, $price, $compare, $stock]) {
                $product->variants()->create([
                    'name' => $name,
                    'sku' => $data['sku'].'-'.($j + 1),
                    'price' => $price,
                    'compare_at_price' => $compare,
                    'stock_quantity' => $stock,
                    'is_default' => $j === 0,
                    'is_active' => true,
                    'sort_order' => $j,
                ]);
            }
        }

        $this->seedShipping();
        $this->seedCoupons();
    }

    private function seedShipping(): void
    {
        $methods = [
            ['name' => 'Giao nhanh nội tỉnh (trại tự giao)', 'description' => 'Xe của trại giao trong ngày, ếch sống khoẻ nhất', 'fee' => 20000, 'free_from_amount' => 500000, 'province_codes' => ['PT'], 'estimated_days' => 'Trong ngày', 'sort_order' => 0],
            ['name' => 'Giao nhanh Hà Nội', 'description' => 'Xe trung chuyển hằng ngày Hòa Bình – Hà Nội', 'fee' => 35000, 'free_from_amount' => 1000000, 'province_codes' => ['HN'], 'estimated_days' => '4–8 giờ', 'sort_order' => 1],
            ['name' => 'Chuyển phát tiêu chuẩn', 'description' => 'Đóng thùng xốp oxy/giữ lạnh, gửi nhà xe hoặc đơn vị vận chuyển', 'fee' => 45000, 'free_from_amount' => 1000000, 'province_codes' => null, 'estimated_days' => '1–3 ngày', 'sort_order' => 2],
            ['name' => 'Hoả tốc (xe khách / máy bay)', 'description' => 'Dành cho đơn ếch giống số lượng lớn đi xa', 'fee' => 120000, 'free_from_amount' => null, 'province_codes' => null, 'estimated_days' => '12–24 giờ', 'sort_order' => 3],
        ];

        foreach ($methods as $m) {
            ShippingMethod::updateOrCreate(['name' => $m['name']], $m + ['is_active' => true]);
        }
    }

    private function seedCoupons(): void
    {
        $coupons = [
            ['code' => 'CHAOBAN', 'description' => 'Giảm 10% cho khách mới, tối đa 100.000đ', 'type' => CouponType::Percent, 'value' => 10, 'max_discount_amount' => 100000, 'min_order_amount' => 300000, 'per_customer_limit' => 1],
            ['code' => 'GIONGKHOE', 'description' => 'Giảm 50.000đ cho đơn ếch giống từ 1 triệu', 'type' => CouponType::Fixed, 'value' => 50000, 'min_order_amount' => 1000000],
            ['code' => 'FREESHIP', 'description' => 'Miễn phí vận chuyển cho đơn từ 500.000đ', 'type' => CouponType::FreeShipping, 'value' => 0, 'min_order_amount' => 500000, 'usage_limit' => 200],
        ];

        foreach ($coupons as $c) {
            Coupon::updateOrCreate(['code' => $c['code']], $c + ['is_active' => true, 'expires_at' => now()->addMonths(6)]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        $giongDesc = <<<'HTML'
<h2>Vì sao chọn ếch giống của trại?</h2>
<p>Ếch giống được chọn lọc từ đàn bố mẹ thuần chủng, ương trong bể xi măng có mái che và <strong>tập ăn cám viên từ 15 ngày tuổi</strong>. Khi xuất trại, ếch đã quen thức ăn công nghiệp nên bà con không mất công tập lại, giảm hao hụt giai đoạn đầu.</p>
<ul>
<li>Kích cỡ đồng đều, không dị tật, da bóng, nhảy khoẻ.</li>
<li>Đã xử lý ký sinh trùng trước khi xuất bán 3 ngày.</li>
<li>Bù 100% số ếch chết khi nhận hàng (có video mở thùng).</li>
<li>Tặng tài liệu kỹ thuật và hỗ trợ qua Zalo suốt vụ nuôi.</li>
</ul>
<h3>Mật độ thả khuyến nghị</h3>
<table><tr><th>Hình thức nuôi</th><th>Mật độ</th></tr><tr><td>Bể bạt, bể xi măng</td><td>80–100 con/m²</td></tr><tr><td>Lồng lưới trên ao</td><td>60–80 con/m²</td></tr><tr><td>Ao đất</td><td>30–50 con/m²</td></tr></table>
<blockquote>Mẹo: thả giống vào sáng sớm hoặc chiều mát, ngâm cả túi ếch xuống nước bể 15 phút cho cân bằng nhiệt độ rồi mới thả.</blockquote>
HTML;

        return [
            [
                'name' => 'Ếch giống Thái Lan size 120–150 con/kg', 'label' => 'Ếch giống Thái Lan', 'sku' => 'EG-THAI', 'category' => 'ech-giong-thai-lan',
                'short' => 'Ếch giống Thái Lan 25–30 ngày tuổi, đã tập ăn cám viên, khoẻ mạnh đồng đều. Bù hàng nếu hao hụt khi nhận.',
                'description' => $giongDesc, 'price' => 1800, 'compare' => null, 'unit' => 'con', 'stock' => 0, 'palette' => 'green', 'motif' => 'frog', 'image_count' => 3, 'featured' => true, 'sold' => 48200,
                'variants' => [['Túi 100 con', 180000, 200000, 400], ['Túi 500 con', 850000, 1000000, 120], ['Túi 1.000 con', 1600000, 2000000, 60], ['Túi 5.000 con', 7500000, 10000000, 8]],
                'specs' => [['label' => 'Giống', 'value' => 'Ếch Thái Lan (Rana rugulosa)'], ['label' => 'Tuổi', 'value' => '25–30 ngày'], ['label' => 'Kích cỡ', 'value' => '120–150 con/kg'], ['label' => 'Thời gian nuôi đến thương phẩm', 'value' => '75–90 ngày'], ['label' => 'Tỷ lệ sống khi nhận', 'value' => '≥ 97%']],
            ],
            [
                'name' => 'Ếch giống lai F1 siêu thịt', 'label' => 'Ếch lai F1', 'sku' => 'EG-F1', 'category' => 'ech-giong',
                'short' => 'Giống lai F1 tăng trọng nhanh, đạt 4–5 con/kg sau 80 ngày. Phù hợp hộ nuôi quy mô vừa và lớn.',
                'description' => $giongDesc, 'price' => 2200, 'unit' => 'con', 'stock' => 0, 'palette' => 'lime', 'motif' => 'frog', 'featured' => true, 'sold' => 21500,
                'variants' => [['Túi 100 con', 220000, 250000, 300], ['Túi 500 con', 1050000, 1250000, 90], ['Túi 1.000 con', 2000000, 2500000, 40]],
                'specs' => [['label' => 'Giống', 'value' => 'Lai F1 (Thái Lan × Đài Loan)'], ['label' => 'Tuổi', 'value' => '30 ngày'], ['label' => 'Kích cỡ', 'value' => '100–120 con/kg'], ['label' => 'Năng suất', 'value' => '4–5 con/kg sau 80 ngày']],
            ],
            [
                'name' => 'Nòng nọc ếch Thái 7 ngày tuổi', 'label' => 'Nòng nọc 7 ngày', 'sku' => 'NN-7', 'category' => 'nong-noc',
                'short' => 'Nòng nọc khoẻ, bơi nhanh, giá rẻ cho hộ đã có kinh nghiệm ương. Giao trong túi oxy.',
                'description' => '<p>Nòng nọc 7 ngày tuổi từ ếch bố mẹ Thái Lan thuần. Phù hợp hộ nuôi có bể ương riêng và đã có kinh nghiệm chuyển giai đoạn biến thái.</p><p><strong>Lưu ý:</strong> giai đoạn biến thái (ngày 20–28) cần giảm mực nước và bổ sung giá thể cho ếch con bám.</p>',
                'price' => 400, 'unit' => 'con', 'stock' => 0, 'palette' => 'sky', 'motif' => 'tadpole', 'sold' => 95000,
                'variants' => [['Túi 1.000 con', 350000, 400000, 200], ['Túi 5.000 con', 1500000, 2000000, 50]],
                'specs' => [['label' => 'Tuổi', 'value' => '7 ngày'], ['label' => 'Đóng gói', 'value' => 'Túi oxy 1.000 con']],
            ],
            [
                'name' => 'Ếch thịt sống loại 1 (4–6 con/kg)', 'label' => 'Ếch thịt loại 1', 'sku' => 'ET-L1', 'category' => 'ech-thit-song',
                'short' => 'Ếch thương phẩm size to, thịt chắc ngọt, giao sống tận nhà. Phù hợp nhà hàng, quán nhậu, bếp gia đình.',
                'description' => '<h2>Ếch đồng nuôi sạch</h2><p>Ếch nuôi bằng cám viên chất lượng, <strong>không dùng kháng sinh trong 30 ngày trước xuất bán</strong>. Thịt trắng, săn, ngọt tự nhiên.</p><ul><li>Giao sống trong thùng xốp đục lỗ, có lót rơm ẩm.</li><li>Hỗ trợ làm sạch miễn phí khi đặt từ 3 kg (ghi chú khi đặt hàng).</li></ul><h3>Gợi ý món ngon</h3><p>Ếch xào sả ớt, ếch om chuối đậu, cháo ếch Singapore, ếch chiên bơ tỏi.</p>',
                'price' => 75000, 'compare' => 85000, 'unit' => 'kg', 'stock' => 320, 'low' => 20, 'palette' => 'teal', 'motif' => 'frog', 'featured' => true, 'sold' => 6200,
                'variants' => [],
                'specs' => [['label' => 'Size', 'value' => '4–6 con/kg'], ['label' => 'Tuổi nuôi', 'value' => '3,5–4 tháng'], ['label' => 'Bảo quản', 'value' => 'Để nơi thoáng mát, dùng trong 24h']],
            ],
            [
                'name' => 'Ếch thịt sống loại 2 (7–9 con/kg)', 'label' => 'Ếch thịt loại 2', 'sku' => 'ET-L2', 'category' => 'ech-thit-song',
                'short' => 'Size vừa, giá mềm, thích hợp nấu cháo, om, nướng xiên.',
                'description' => '<p>Ếch size vừa, thịt mềm, giá kinh tế. Rất hợp nấu cháo ếch cho trẻ nhỏ và người già.</p>',
                'price' => 62000, 'unit' => 'kg', 'stock' => 4, 'low' => 20, 'palette' => 'teal', 'motif' => 'frog', 'sold' => 3100,
                'variants' => [],
                'specs' => [['label' => 'Size', 'value' => '7–9 con/kg']],
            ],
            [
                'name' => 'Thịt ếch làm sạch nguyên con', 'label' => 'Ếch làm sạch', 'sku' => 'TE-NC', 'category' => 'thit-ech-lam-sach',
                'short' => 'Ếch làm sạch trong ngày, bỏ da và nội tạng, cấp đông nhanh. Rã đông là nấu được ngay.',
                'description' => '<p>Ếch được sơ chế tại xưởng của trại theo quy trình khép kín, <strong>đóng khay hút chân không 500 g</strong>, cấp đông -18°C.</p><ul><li>Không chất bảo quản, không ngâm hoá chất.</li><li>Bảo quản ngăn đá đến 3 tháng.</li></ul>',
                'price' => 135000, 'unit' => 'kg', 'stock' => 0, 'palette' => 'rose', 'motif' => 'legs', 'featured' => true, 'sold' => 4800,
                'variants' => [['Khay 500 g', 72000, 80000, 150], ['Túi 1 kg', 135000, 150000, 90], ['Thùng 5 kg (nhà hàng)', 640000, 750000, 15]],
                'specs' => [['label' => 'Quy cách', 'value' => 'Khay hút chân không'], ['label' => 'Bảo quản', 'value' => 'Ngăn đá -18°C, 3 tháng']],
            ],
            [
                'name' => 'Đùi ếch làm sạch', 'label' => 'Đùi ếch', 'sku' => 'TE-DUI', 'category' => 'thit-ech-lam-sach',
                'short' => 'Phần ngon nhất của con ếch: đùi to, thịt dày. Chuyên dùng cho món chiên bơ, nướng, lẩu.',
                'description' => '<p>Đùi ếch tuyển chọn từ ếch loại 1, cắt gọn, rửa sạch nhớt. Thịt dày, ít xương, ngọt.</p><h3>Món gợi ý</h3><ol><li>Đùi ếch chiên bơ tỏi</li><li>Đùi ếch nướng muối ớt</li><li>Lẩu ếch măng chua</li></ol>',
                'price' => 210000, 'compare' => 240000, 'unit' => 'kg', 'stock' => 45, 'low' => 10, 'palette' => 'rose', 'motif' => 'legs', 'sold' => 2300,
                'variants' => [],
                'specs' => [['label' => 'Quy cách', 'value' => 'Túi 1 kg hút chân không']],
            ],
            [
                'name' => 'Chả ếch Hòa Bình', 'label' => 'Chả ếch', 'sku' => 'CB-CHA', 'category' => 'che-bien-san',
                'short' => 'Chả ếch quết tay với thì là, hành lá — dai giòn, thơm. Chiên vàng là ăn ngay.',
                'description' => '<p>Thịt ếch tươi được lọc xương, quết tay cùng thì là, hành lá, tiêu sọ. <strong>Không hàn the, không phụ gia</strong>.</p><p>Hướng dẫn: rã đông 10 phút, chiên lửa vừa đến khi vàng hai mặt.</p>',
                'price' => 240000, 'unit' => 'kg', 'stock' => 60, 'palette' => 'amber', 'motif' => 'cake', 'featured' => true, 'sold' => 1900,
                'variants' => [['Hộp 500 g', 125000, null, 80], ['Hộp 1 kg', 240000, 260000, 40]],
                'specs' => [['label' => 'Thành phần', 'value' => 'Thịt ếch 85%, thì là, hành lá, gia vị'], ['label' => 'Hạn dùng', 'value' => '3 tháng (ngăn đá)']],
            ],
            [
                'name' => 'Ếch tẩm ướp sả ớt', 'label' => 'Ếch ướp sả ớt', 'sku' => 'CB-SAOT', 'category' => 'che-bien-san',
                'short' => 'Ếch chặt miếng, ướp sẵn sả ớt theo công thức của trại. 15 phút có món nhậu đậm đà.',
                'description' => '<p>Ếch tươi chặt miếng vừa ăn, ướp sả, ớt, nước mắm Phú Quốc. Đóng khay 500 g, đủ cho 3–4 người.</p>',
                'price' => 95000, 'unit' => 'khay', 'stock' => 3, 'low' => 10, 'palette' => 'amber', 'motif' => 'legs', 'sold' => 870,
                'variants' => [],
                'specs' => [['label' => 'Khối lượng', 'value' => '500 g/khay']],
            ],
            [
                'name' => 'Cám viên cho ếch giai đoạn đầu (42% đạm)', 'label' => 'Cám ếch 42% đạm', 'sku' => 'VT-CAM42', 'category' => 'thuc-an-vat-tu',
                'short' => 'Cám viên nổi 1,5 mm, đạm 42% cho ếch 1–30 ngày sau thả. Ếch ăn mạnh, lớn nhanh, ít hao.',
                'description' => '<p>Cám viên nổi chuyên dùng cho ếch giai đoạn đầu, hàm lượng đạm cao giúp ếch phát triển khung xương. Viên nổi lâu, không làm bẩn nước.</p><table><tr><th>Chỉ tiêu</th><th>Hàm lượng</th></tr><tr><td>Đạm thô</td><td>42%</td></tr><tr><td>Béo</td><td>5%</td></tr><tr><td>Kích thước viên</td><td>1,5 mm</td></tr></table>',
                'price' => 520000, 'unit' => 'bao', 'stock' => 85, 'palette' => 'stone', 'motif' => 'bag', 'sold' => 1200,
                'variants' => [],
                'specs' => [['label' => 'Quy cách', 'value' => 'Bao 20 kg'], ['label' => 'Đạm', 'value' => '42%']],
            ],
            [
                'name' => 'Cám viên ếch thương phẩm (35% đạm)', 'label' => 'Cám ếch 35% đạm', 'sku' => 'VT-CAM35', 'category' => 'thuc-an-vat-tu',
                'short' => 'Cám viên 3–4 mm cho ếch từ 30 ngày đến xuất bán. Tối ưu FCR, thịt ếch chắc.',
                'description' => '<p>Cám viên nổi cho giai đoạn nuôi thịt, cân đối đạm – năng lượng giúp hệ số chuyển đổi thức ăn (FCR) chỉ 1,1–1,2.</p>',
                'price' => 445000, 'unit' => 'bao', 'stock' => 140, 'palette' => 'stone', 'motif' => 'bag', 'sold' => 2600,
                'variants' => [],
                'specs' => [['label' => 'Quy cách', 'value' => 'Bao 20 kg'], ['label' => 'Đạm', 'value' => '35%']],
            ],
            [
                'name' => 'Bể bạt nuôi ếch khung sắt 2×4 m', 'label' => 'Bể bạt 2×4 m', 'sku' => 'VT-BAT24', 'category' => 'thuc-an-vat-tu',
                'short' => 'Bể bạt HDPE dày 0,5 mm kèm khung sắt mạ kẽm, lắp đặt 30 phút. Nuôi được 600–800 ếch/bể.',
                'description' => '<p>Bộ bể bạt hoàn chỉnh gồm bạt HDPE 0,5 mm, khung sắt mạ kẽm, ống xả đáy và lưới che. Bền 4–5 năm.</p>',
                'price' => 1850000, 'compare' => 2100000, 'unit' => 'bộ', 'stock' => 12, 'low' => 3, 'palette' => 'sky', 'motif' => 'farm', 'sold' => 340,
                'variants' => [],
                'specs' => [['label' => 'Kích thước', 'value' => '2 × 4 × 0,8 m'], ['label' => 'Chất liệu bạt', 'value' => 'HDPE 0,5 mm'], ['label' => 'Bảo hành', 'value' => '12 tháng']],
            ],
        ];
    }
}
