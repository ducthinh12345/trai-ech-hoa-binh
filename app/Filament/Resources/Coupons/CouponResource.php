<?php

namespace App\Filament\Resources\Coupons;

use App\Enums\CouponType;
use App\Filament\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'mã giảm giá';

    protected static ?string $pluralModelLabel = 'Mã giảm giá';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('code')->label('Mã')->required()->unique(ignoreRecord: true)->maxLength(50)
                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                    ->suffixAction(Action::make('random')->icon('heroicon-o-sparkles')->tooltip('Tạo mã ngẫu nhiên')
                        ->action(fn (Set $set) => $set('code', strtoupper(Str::random(8))))),
                TextInput::make('description')->label('Mô tả nội bộ')->placeholder('VD: Khuyến mãi khai trương'),
            ]),
            ToggleButtons::make('type')->label('Loại giảm giá')->options(CouponType::class)->inline()->required()->live()->default(CouponType::Percent),
            Grid::make(2)->schema([
                TextInput::make('value')->label(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? 'Phần trăm giảm' : 'Số tiền giảm')
                    ->numeric()->required()->minValue(0)
                    ->suffix(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? '%' : 'đ')
                    ->maxValue(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? 100 : null)
                    ->hidden(fn (Get $get) => self::typeOf($get) === CouponType::FreeShipping)
                    ->default(0),
                TextInput::make('max_discount_amount')->label('Giảm tối đa')->numeric()->suffix('đ')->helperText('Để trống = không giới hạn')
                    ->visible(fn (Get $get) => self::typeOf($get) === CouponType::Percent),
                TextInput::make('min_order_amount')->label('Đơn tối thiểu')->numeric()->suffix('đ')->default(0),
            ]),
            Section::make('Giới hạn sử dụng')->compact()->columns(2)->schema([
                TextInput::make('usage_limit')->label('Tổng số lượt')->numeric()->helperText('Để trống = không giới hạn'),
                TextInput::make('per_customer_limit')->label('Mỗi khách (có tài khoản) dùng tối đa')->numeric(),
                DateTimePicker::make('starts_at')->label('Bắt đầu')->native(false)->displayFormat('d/m/Y H:i'),
                DateTimePicker::make('expires_at')->label('Hết hạn')->native(false)->displayFormat('d/m/Y H:i')->after('starts_at'),
            ]),
            Toggle::make('is_active')->label('Đang kích hoạt')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->copyable()->weight('bold')->fontFamily('mono')
                    ->description(fn (Coupon $record) => $record->description),
                TextColumn::make('summary')->label('Ưu đãi')->state(fn (Coupon $record) => $record->summary())
                    ->description(fn (Coupon $record) => $record->min_order_amount ? 'Đơn từ '.money($record->min_order_amount) : null),
                TextColumn::make('used_count')->label('Đã dùng')->alignCenter()
                    ->formatStateUsing(fn (Coupon $record) => $record->used_count.($record->usage_limit ? ' / '.$record->usage_limit : '')),
                TextColumn::make('expires_at')->label('Hết hạn')->dateTime()->placeholder('Không hết hạn')->sortable()
                    ->color(fn (Coupon $record) => $record->expires_at?->isPast() ? 'danger' : null),
                TextColumn::make('validity')->label('Tình trạng')->badge()
                    ->state(fn (Coupon $record) => $record->isCurrentlyValid() ? 'Đang chạy' : 'Không hiệu lực')
                    ->color(fn (string $state) => $state === 'Đang chạy' ? 'success' : 'gray'),
                ToggleColumn::make('is_active')->label('Bật'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Nhân bản')->excludeAttributes(['used_count'])
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'code' => $data['code'].'-'.strtoupper(Str::random(3))]),
                DeleteAction::make(),
            ]);
    }

    /**
     * Trạng thái form có thể là enum (khi sửa) hoặc chuỗi (khi vừa bấm chọn).
     */
    private static function typeOf(Get $get): ?CouponType
    {
        $type = $get('type');

        return $type instanceof CouponType ? $type : CouponType::tryFrom((string) $type);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCoupons::route('/')];
    }
}
