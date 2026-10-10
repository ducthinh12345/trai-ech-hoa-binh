<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\Support\DemoArt;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            ['title' => 'Ếch giống Thái Lan khoẻ – lớn nhanh – ít hao', 'subtitle' => 'Đã tập ăn cám viên, bù 100% ếch chết khi nhận. Giao toàn quốc trong 24–48h.', 'link' => '/danh-muc/ech-giong', 'button_text' => 'Chọn ếch giống', 'palette' => 'green', 'motif' => 'frog'],
            ['title' => 'Thịt ếch làm sạch – ngọt thịt, tiện nấu', 'subtitle' => 'Sơ chế trong ngày, hút chân không, cấp đông nhanh. Không hoá chất.', 'link' => '/danh-muc/thit-ech-lam-sach', 'button_text' => 'Mua thịt ếch', 'palette' => 'rose', 'motif' => 'legs'],
            ['title' => 'Trọn bộ vật tư nuôi ếch cho người mới', 'subtitle' => 'Bể bạt, cám viên, men vi sinh — kỹ sư của trại tư vấn miễn phí từ A–Z.', 'link' => '/danh-muc/thuc-an-vat-tu', 'button_text' => 'Xem vật tư', 'palette' => 'teal', 'motif' => 'farm'],
        ];
        foreach ($banners as $i => $b) {
            Banner::updateOrCreate(['title' => $b['title']], [
                'subtitle' => $b['subtitle'], 'link' => $b['link'], 'button_text' => $b['button_text'],
                'image' => DemoArt::banner("banners/hero-{$i}.svg", $b['palette'], $b['motif']),
                'position' => Banner::POSITION_HERO, 'sort_order' => $i, 'is_active' => true,
            ]);
        }
        foreach ([['Combo nuôi thử 500 ếch giống', 'Tặng 1 bao cám 42% đạm + tài liệu kỹ thuật', '/danh-muc/ech-giong', 'amber', 'frog'], ['Chả ếch quết tay – giảm 10%', 'Nhập mã CHAOBAN cho đơn đầu tiên', '/danh-muc/che-bien-san', 'stone', 'cake']] as $i => [$title, $sub, $link, $palette, $motif]) {
            Banner::updateOrCreate(['title' => $title], [
                'subtitle' => $sub, 'link' => $link, 'button_text' => 'Xem ngay',
                'image' => DemoArt::banner("banners/promo-{$i}.svg", $palette, $motif),
                'position' => Banner::POSITION_PROMO, 'sort_order' => $i, 'is_active' => true,
            ]);
        }

        $cats = collect([
            'ky-thuat-nuoi-ech' => 'Kỹ thuật nuôi ếch',
            'mon-ngon-tu-ech' => 'Món ngon từ ếch',
            'tin-tuc-trai' => 'Tin tức từ trại',
        ])->map(fn ($name, $slug) => PostCategory::updateOrCreate(['slug' => $slug], ['name' => $name]));

        $author = User::query()->where('role', 'admin')->first();
        $posts = [
            ['ky-thuat-nuoi-ech', 'Kỹ thuật nuôi ếch Thái Lan trong bể bạt cho người mới bắt đầu', 'Hướng dẫn chi tiết từ chuẩn bị bể, chọn giống, mật độ thả đến chăm sóc và phòng bệnh — áp dụng được ngay cho hộ nuôi quy mô nhỏ.', 'green', 'farm', true],
            ['ky-thuat-nuoi-ech', '5 bệnh thường gặp ở ếch và cách phòng trị hiệu quả', 'Bệnh đỏ chân, chướng bụng, mù mắt, lở loét và ký sinh trùng: dấu hiệu nhận biết sớm và phác đồ xử lý an toàn.', 'teal', 'frog', false],
            ['ky-thuat-nuoi-ech', 'Cách chọn ếch giống chất lượng: 7 tiêu chí cần nhớ', 'Ếch giống quyết định 70% thành công của vụ nuôi. Đây là cách kiểm tra nhanh tại trại hoặc khi mở thùng hàng.', 'lime', 'tadpole', false],
            ['mon-ngon-tu-ech', 'Cách làm ếch xào sả ớt thơm nức, thịt không bị dai', 'Bí quyết sơ chế khử tanh và canh lửa để món ếch xào sả ớt giòn thịt, thấm vị.', 'amber', 'legs', true],
            ['mon-ngon-tu-ech', 'Cháo ếch Singapore chuẩn vị cho cả nhà', 'Công thức cháo trắng ninh nhừ ăn kèm ếch kho tộ cay nồng — món bồi bổ lý tưởng mùa lạnh.', 'rose', 'legs', false],
            ['tin-tuc-trai', 'Trại mở rộng khu ương nòng nọc, tăng sản lượng giống mùa 2026', 'Khu ương mới 2.000 m² có mái che giúp trại cung ứng ổn định ếch giống quanh năm.', 'sky', 'farm', false],
        ];
        foreach ($posts as $i => [$cat, $title, $excerpt, $palette, $motif, $featured]) {
            $slug = str($title)->slug()->toString();
            Post::updateOrCreate(['slug' => $slug], [
                'post_category_id' => $cats[$cat]->id,
                'author_id' => $author?->id,
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $this->postBody($title),
                'cover_image' => DemoArt::cover("posts/{$slug}.svg", $title, $palette, $motif),
                'is_published' => true,
                'is_featured' => $featured,
                'published_at' => now()->subDays(($i + 1) * 4),
                'views_count' => random_int(120, 3200),
            ]);
        }

        $pages = [
            ['Chính sách đổi trả & bảo hành', 'chinh-sach-doi-tra', '<h2>Cam kết với ếch sống và ếch giống</h2><p>Trại bù 100% số ếch chết khi nhận hàng. Vui lòng <strong>quay video khi mở thùng</strong> và báo cho trại trong vòng 2 giờ kể từ khi nhận.</p><h2>Với sản phẩm đông lạnh, chế biến sẵn</h2><p>Đổi mới nếu sản phẩm bị rã đông, hở bao bì hoặc có mùi lạ khi nhận.</p><h2>Vật tư</h2><p>Bể bạt bảo hành 12 tháng lỗi kỹ thuật. Cám chưa mở bao được đổi trong 7 ngày.</p>'],
            ['Chính sách vận chuyển', 'chinh-sach-van-chuyen', '<p>Trại giao hàng toàn quốc qua xe của trại (nội tỉnh, Hà Nội) và đối tác vận chuyển. Ếch sống được đóng thùng xốp đục lỗ, ếch giống đóng túi oxy.</p><ul><li>Miễn phí vận chuyển cho đơn từ 1.000.000đ (trừ hoả tốc).</li><li>Thời gian: trong ngày (nội tỉnh), 1–3 ngày (tỉnh khác).</li></ul>'],
            ['Hướng dẫn mua hàng & thanh toán', 'huong-dan-mua-hang', '<ol><li>Chọn sản phẩm, quy cách và bấm <strong>Thêm vào giỏ</strong>.</li><li>Vào giỏ hàng, nhập mã giảm giá (nếu có) rồi bấm <strong>Tiến hành đặt hàng</strong>.</li><li>Điền thông tin nhận hàng, chọn cách giao và cách thanh toán.</li><li>Trại gọi xác nhận trong ít phút.</li></ol><h2>Các hình thức thanh toán</h2><ul><li>COD — trả tiền khi nhận hàng.</li><li>Chuyển khoản quét mã VietQR.</li><li>VNPay (thẻ ATM, QR ngân hàng, Visa/Master).</li><li>Ví MoMo.</li></ul>'],
            ['Chính sách bảo mật', 'chinh-sach-bao-mat', '<p>Thông tin khách hàng (họ tên, số điện thoại, địa chỉ) chỉ được dùng để giao hàng và chăm sóc sau bán. Trại không chia sẻ thông tin cho bên thứ ba ngoài đơn vị vận chuyển.</p><p>Thanh toán online được xử lý trực tiếp tại cổng VNPay/MoMo; website không lưu thông tin thẻ.</p>'],
        ];
        foreach ($pages as $i => [$title, $slug, $content]) {
            Page::updateOrCreate(['slug' => $slug], ['title' => $title, 'content' => $content, 'is_published' => true, 'show_in_footer' => true, 'sort_order' => $i]);
        }

        $faqs = [
            ['Ếch giống có được bảo hành khi vận chuyển xa không?', 'Có. Trại bù 100% số ếch chết khi nhận nếu bạn quay video lúc mở thùng và báo trong 2 giờ.'],
            ['Người mới nuôi nên bắt đầu với bao nhiêu con?', 'Trại khuyên bắt đầu với 500–1.000 con trong 1–2 bể bạt 2×4 m để làm quen quy trình, sau đó mới mở rộng.'],
            ['Trại có thu mua lại ếch thương phẩm không?', 'Có. Với hộ mua giống tại trại, chúng tôi hỗ trợ bao tiêu đầu ra theo giá thị trường tại thời điểm xuất bán.'],
            ['Tôi có thể đến tham quan trại không?', 'Rất hoan nghênh! Vui lòng gọi hotline đặt lịch trước để kỹ sư của trại sắp xếp hướng dẫn.'],
            ['Thanh toán online có an toàn không?', 'Thanh toán được xử lý trực tiếp tại cổng VNPay/MoMo được Ngân hàng Nhà nước cấp phép. Website không lưu thông tin thẻ của bạn.'],
        ];
        foreach ($faqs as $i => [$q, $a]) {
            Faq::updateOrCreate(['question' => $q], ['answer' => "<p>{$a}</p>", 'sort_order' => $i, 'is_active' => true]);
        }

        $testimonials = [
            ['Anh Nguyễn Văn Thắng', 'Hộ nuôi 20 bể bạt – Phú Thọ', 'Mua giống ở trại 3 vụ rồi, ếch đều con, tỷ lệ sống trên 90%. Kỹ sư hỗ trợ qua Zalo rất nhiệt tình, kể cả lúc nửa đêm.'],
            ['Chị Lê Thị Hoa', 'Chủ quán Lẩu Ếch Hoa – Hà Nội', 'Nhập ếch thịt và đùi ếch mỗi tuần, hàng tươi, giao đúng giờ. Khách quán khen thịt ếch ngọt hơn chỗ cũ.'],
            ['Anh Trần Minh Đức', 'Mới khởi nghiệp nuôi ếch – Thanh Hóa', 'Là người mới hoàn toàn, mình được trại tư vấn từ cách dựng bể đến cho ăn. Vụ đầu đã có lãi, sắp mở rộng thêm 10 bể.'],
        ];
        foreach ($testimonials as $i => [$name, $title, $content]) {
            Testimonial::updateOrCreate(['customer_name' => $name], ['customer_title' => $title, 'content' => $content, 'rating' => 5, 'sort_order' => $i, 'is_active' => true]);
        }
    }

    private function postBody(string $title): string
    {
        return <<<HTML
<p>Bài viết <strong>{$title}</strong> tổng hợp kinh nghiệm thực tế hơn 10 năm của đội ngũ kỹ thuật tại trại.</p>
<h2>1. Chuẩn bị</h2>
<p>Trước khi bắt đầu, hãy chuẩn bị đầy đủ dụng cụ, nguồn nước sạch và không gian thoáng mát. Nguồn nước nên có pH 6,5–7,5, không nhiễm phèn, nhiễm mặn.</p>
<ul><li>Vệ sinh và khử trùng dụng cụ, bể nuôi.</li><li>Kiểm tra nhiệt độ nước, lý tưởng 25–30°C.</li><li>Chuẩn bị thức ăn phù hợp từng giai đoạn.</li></ul>
<h2>2. Thực hiện</h2>
<p>Tuân thủ đúng quy trình từng bước, ghi chép lại để rút kinh nghiệm cho các lần sau. Đừng ngại gọi kỹ sư của trại khi gặp tình huống lạ.</p>
<blockquote>Kinh nghiệm: quan sát hành vi ăn của ếch mỗi ngày là cách phát hiện bệnh sớm nhất.</blockquote>
<h2>3. Lưu ý quan trọng</h2>
<p>Thay nước định kỳ, không để thức ăn thừa tồn đọng, bổ sung men vi sinh và vitamin C vào thời điểm giao mùa.</p>
HTML;
    }
}
