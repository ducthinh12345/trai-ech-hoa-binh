<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Trang Cài đặt gom theo tab. Mỗi tab là một "group" trong bảng settings (statePath = tên group)
 * nên thêm ô mới chỉ cần khai báo field, không phải sửa logic lưu.
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Cài đặt cửa hàng';

    protected static ?string $title = 'Cài đặt cửa hàng';

    /** @var array<string, array<string, mixed>>|null */
    public ?array $data = [];

    private const GROUPS = ['general', 'home', 'payment', 'seo', 'social', 'notification'];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $rows = Setting::query()->get(['group', 'key', 'value']);
        $data = array_fill_keys(self::GROUPS, []);

        foreach ($rows as $row) {
            $value = is_array($row->value) && array_key_exists('__scalar', $row->value) ? $row->value['__scalar'] : $row->value;
            $data[in_array($row->group, self::GROUPS, true) ? $row->group : 'general'][$row->key] = $value;
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make('settings')->persistTabInQueryString()->tabs([
                Tab::make('Cửa hàng')->icon(Heroicon::OutlinedBuildingStorefront)->schema([
                    Group::make()->statePath('general')->schema([
                        Section::make('Thông tin liên hệ')->description('Hiển thị ở đầu trang, chân trang, trang liên hệ và hoá đơn.')->columns(2)->schema([
                            TextInput::make('store_name')->label('Tên cửa hàng')->required(),
                            TextInput::make('store_tagline')->label('Khẩu hiệu'),
                            TextInput::make('store_phone')->label('Hotline')->tel()->required(),
                            TextInput::make('store_zalo')->label('Số Zalo')->helperText('Dùng cho nút "Chat Zalo" nổi.'),
                            TextInput::make('store_email')->label('Email')->email(),
                            TextInput::make('store_working_hours')->label('Giờ làm việc'),
                            TextInput::make('store_address')->label('Địa chỉ trại')->columnSpanFull(),
                            Textarea::make('store_description')->label('Giới thiệu ngắn (chân trang)')->rows(2)->columnSpanFull(),
                            TextInput::make('business_license')->label('Thông tin giấy phép kinh doanh')->columnSpanFull(),
                        ]),
                        Section::make('Logo & biểu tượng')->columns(2)->collapsible()->schema([
                            FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('branding')->helperText('Để trống để dùng logo con ếch mặc định. Nên dùng PNG nền trong suốt, cao 96px.'),
                            FileUpload::make('favicon')->label('Favicon (icon tab trình duyệt)')->image()->disk('public')->directory('branding'),
                        ]),
                        Section::make('Bán hàng')->columns(2)->schema([
                            TextInput::make('free_shipping_threshold')->label('Hiện thanh "Mua thêm X để miễn phí ship" khi đơn dưới')->numeric()->suffix('đ')
                                ->helperText('Chỉ để hiển thị nhắc khách. Mức miễn phí thật cài ở mục "Phí vận chuyển". Để 0 để ẩn.'),
                            TextInput::make('announcement_text')->label('Dòng thông báo đầu trang')->helperText('Để trống để ẩn.'),
                        ]),
                    ]),
                ]),

                Tab::make('Trang chủ')->icon(Heroicon::OutlinedHome)->schema([
                    Group::make()->statePath('home')->schema([
                        Section::make('Khối "Câu chuyện của trại"')->columns(2)->schema([
                            TextInput::make('about_title')->label('Tiêu đề')->columnSpanFull(),
                            Textarea::make('about_text')->label('Nội dung')->rows(4)->columnSpanFull(),
                            FileUpload::make('about_image')->label('Ảnh trại')->image()->disk('public')->directory('branding')->imageEditor()->columnSpanFull(),
                            TextInput::make('stat_years')->label('Số năm kinh nghiệm')->placeholder('10+'),
                            TextInput::make('stat_customers')->label('Số hộ nuôi tin dùng')->placeholder('5.000+'),
                            TextInput::make('stat_provinces')->label('Số tỉnh giao hàng')->placeholder('34'),
                        ]),
                    ]),
                ]),

                Tab::make('Thanh toán')->icon(Heroicon::OutlinedCreditCard)->schema([
                    Group::make()->statePath('payment')->schema([
                        Section::make('Thanh toán khi nhận hàng (COD)')->schema([
                            Toggle::make('payment_cod_enabled')->label('Cho phép COD'),
                        ]),
                        Section::make('Chuyển khoản ngân hàng (mã VietQR)')
                            ->description('Khách quét mã QR bằng app ngân hàng, số tiền và nội dung được điền sẵn. Sau khi nhận tiền, bấm "Đã nhận tiền" trong đơn hàng.')
                            ->columns(2)->schema([
                                Toggle::make('payment_bank_enabled')->label('Cho phép chuyển khoản')->columnSpanFull(),
                                TextInput::make('bank_name')->label('Tên ngân hàng')->placeholder('Vietcombank'),
                                TextInput::make('bank_bin')->label('Mã BIN ngân hàng')->placeholder('970436')
                                    ->helperText('Vietcombank 970436 · Techcombank 970407 · BIDV 970418 · Vietinbank 970415 · MB 970422 · Agribank 970405 · ACB 970416 · VPBank 970432 · TPBank 970423 · Sacombank 970403'),
                                TextInput::make('bank_account_number')->label('Số tài khoản'),
                                TextInput::make('bank_account_name')->label('Tên chủ tài khoản (không dấu)')->placeholder('NGUYEN VAN A'),
                            ]),
                        Section::make('Cổng VNPay')
                            ->description('Đăng ký tại vnpay.vn để nhận Terminal ID và Secret Key. Cấu hình IPN URL trong trang quản trị VNPay là: '.route('payment.vnpay.ipn'))
                            ->collapsible()->columns(2)->schema([
                                Toggle::make('payment_vnpay_enabled')->label('Bật VNPay')->live()->columnSpanFull(),
                                TextInput::make('vnpay_tmn_code')->label('Terminal ID (vnp_TmnCode)')->required(fn (Get $get) => $get('payment_vnpay_enabled')),
                                TextInput::make('vnpay_hash_secret')->label('Secret Key (vnp_HashSecret)')->password()->revealable()->required(fn (Get $get) => $get('payment_vnpay_enabled')),
                                TextInput::make('vnpay_url')->label('URL cổng thanh toán')->url()->columnSpanFull()
                                    ->helperText('Sandbox: https://sandbox.vnpayment.vn/paymentv2/vpcpay.html · Thật: https://pay.vnpay.vn/vpcpay.html'),
                            ]),
                        Section::make('Ví MoMo')
                            ->description('Đăng ký tại business.momo.vn. IPN URL tự gửi kèm mỗi giao dịch: '.route('payment.momo.ipn'))
                            ->collapsible()->columns(2)->schema([
                                Toggle::make('payment_momo_enabled')->label('Bật MoMo')->live()->columnSpanFull(),
                                TextInput::make('momo_partner_code')->label('Partner Code')->required(fn (Get $get) => $get('payment_momo_enabled')),
                                TextInput::make('momo_access_key')->label('Access Key')->required(fn (Get $get) => $get('payment_momo_enabled')),
                                TextInput::make('momo_secret_key')->label('Secret Key')->password()->revealable()->required(fn (Get $get) => $get('payment_momo_enabled')),
                                TextInput::make('momo_endpoint')->label('Endpoint')->url()
                                    ->helperText('Test: https://test-payment.momo.vn/v2/gateway/api/create · Thật: https://payment.momo.vn/v2/gateway/api/create'),
                            ]),
                    ]),
                ]),

                Tab::make('SEO & theo dõi')->icon(Heroicon::OutlinedChartBar)->schema([
                    Group::make()->statePath('seo')->schema([
                        Section::make('SEO mặc định')->schema([
                            Textarea::make('seo_default_description')->label('Mô tả trang chủ (meta description)')->rows(3)->maxLength(320)
                                ->helperText('120–160 ký tự, có từ khoá chính như "ếch giống", "thịt ếch".'),
                            Toggle::make('seo_noindex_site')->label('Chặn Google index toàn site')->helperText('Chỉ bật khi đang xây dựng/thử nghiệm.'),
                            Text::make('Sitemap: '.route('sitemap').' — gửi link này trong Google Search Console.'),
                        ]),
                        Section::make('Mã theo dõi')->columns(2)->schema([
                            TextInput::make('google_site_verification')->label('Google Search Console (mã xác minh)')->placeholder('Chỉ phần content="..."'),
                            TextInput::make('google_analytics_id')->label('Google Analytics 4 ID')->placeholder('G-XXXXXXXXXX'),
                            TextInput::make('facebook_pixel_id')->label('Facebook Pixel ID')->placeholder('123456789012345'),
                        ]),
                        Section::make('Mã tuỳ chỉnh (nâng cao)')->collapsible()->collapsed()
                            ->description('Dán mã chat (Tawk, Messenger), Google Tag Manager... Chỉ dùng mã từ nguồn tin cậy.')
                            ->schema([
                                Textarea::make('custom_head_code')->label('Chèn vào <head>')->rows(4),
                                Textarea::make('custom_body_code')->label('Chèn cuối <body>')->rows(4),
                            ]),
                    ]),
                ]),

                Tab::make('Mạng xã hội & bản đồ')->icon(Heroicon::OutlinedShare)->schema([
                    Group::make()->statePath('social')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('social_facebook')->label('Facebook')->url()->prefixIcon('heroicon-o-link'),
                            TextInput::make('social_youtube')->label('YouTube')->url()->prefixIcon('heroicon-o-link'),
                            TextInput::make('social_tiktok')->label('TikTok')->url()->prefixIcon('heroicon-o-link'),
                        ]),
                        TextInput::make('store_map_embed')->label('Link nhúng Google Maps')
                            ->helperText('Google Maps → Chia sẻ → Nhúng bản đồ → chỉ copy phần link trong src="..."'),
                    ]),
                ]),

                Tab::make('Thông báo')->icon(Heroicon::OutlinedBell)->schema([
                    Group::make()->statePath('notification')->schema([
                        TextInput::make('order_notify_emails')->label('Email nhận thông báo đơn mới')->placeholder('chutrai@gmail.com, ketoan@gmail.com')
                            ->helperText('Nhiều email cách nhau bởi dấu phẩy. Thông báo trong trang quản trị (chuông góc trên) luôn được bật.'),
                    ]),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Lưu cài đặt')->icon('heroicon-o-check')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky()->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::GROUPS as $group) {
            Setting::setMany($state[$group] ?? [], $group);
        }

        Setting::flushCache();
        AppServiceProvider::flushStorefrontCache();

        Notification::make()->title('Đã lưu cài đặt')->body('Thay đổi có hiệu lực ngay trên website.')->success()->send();
    }
}
