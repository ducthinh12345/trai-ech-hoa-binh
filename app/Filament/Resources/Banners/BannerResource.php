<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung website';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'banner';

    protected static ?string $pluralModelLabel = 'Banner trang chủ';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ToggleButtons::make('position')->label('Vị trí')->inline()->required()->default(Banner::POSITION_HERO)
                ->options([Banner::POSITION_HERO => 'Slider đầu trang', Banner::POSITION_PROMO => 'Khối khuyến mãi giữa trang'])
                ->icons([Banner::POSITION_HERO => 'heroicon-o-rectangle-stack', Banner::POSITION_PROMO => 'heroicon-o-megaphone']),
            FileUpload::make('image')->label('Ảnh banner')->image()->required()->disk('public')->directory('banners')->imageEditor()
                ->imageEditorAspectRatioOptions(['16:6', '16:9', '8:3'])
                ->helperText('Slider: nên dùng ảnh 1600×600. Khuyến mãi: 800×300. Chữ hiển thị đè bên trái ảnh.'),
            TextInput::make('title')->label('Tiêu đề lớn')->required(),
            TextInput::make('subtitle')->label('Dòng mô tả'),
            Grid::make(2)->schema([
                TextInput::make('button_text')->label('Chữ trên nút')->placeholder('Mua ngay'),
                TextInput::make('link')->label('Bấm vào dẫn tới')->placeholder('/danh-muc/ech-giong'),
                DateTimePicker::make('starts_at')->label('Hiện từ')->native(false)->displayFormat('d/m/Y H:i'),
                DateTimePicker::make('ends_at')->label('Ẩn sau')->native(false)->displayFormat('d/m/Y H:i')->helperText('Dùng cho banner khuyến mãi có thời hạn.'),
            ]),
            Toggle::make('is_active')->label('Đang hiển thị')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('public')->imageHeight(56)->imageWidth(150),
                TextColumn::make('title')->label('Tiêu đề')->weight('semibold')->wrap()->description(fn (Banner $r) => $r->link),
                TextColumn::make('position')->label('Vị trí')->badge()
                    ->formatStateUsing(fn (string $state) => $state === Banner::POSITION_HERO ? 'Slider' : 'Khuyến mãi'),
                TextColumn::make('ends_at')->label('Hết hạn')->dateTime()->placeholder('Không'),
                ToggleColumn::make('is_active')->label('Hiện'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBanners::route('/')];
    }
}
