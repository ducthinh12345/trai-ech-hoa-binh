<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\SeoFields;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'sản phẩm';

    protected static ?string $pluralModelLabel = 'Sản phẩm';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Thông tin cơ bản')->icon('heroicon-o-information-circle')->schema([
                    TextInput::make('name')
                        ->label('Tên sản phẩm')
                        ->placeholder('VD: Ếch giống Thái Lan size 120 con/kg')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                            if ($operation === 'create' || blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Đường dẫn (URL)')
                        ->prefix(rtrim(config('app.url'), '/').'/san-pham/')
                        ->helperText('Tự tạo từ tên. Nếu đổi đường dẫn, link cũ sẽ tự chuyển hướng sang link mới.')
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Textarea::make('short_description')
                        ->label('Mô tả ngắn')
                        ->helperText('1–2 câu hiện ngay dưới tên sản phẩm và trong kết quả tìm kiếm.')
                        ->rows(2)
                        ->maxLength(500),
                ]),

                Section::make('Hình ảnh')->icon('heroicon-o-photo')
                    ->description('Kéo thả để sắp xếp. Ảnh đầu tiên là ảnh đại diện. Nên dùng ảnh vuông, tối thiểu 800×800.')
                    ->schema([
                        FileUpload::make('images')
                            ->hiddenLabel()
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->maxFiles(10)
                            ->maxSize(4096)
                            ->disk('public')
                            ->directory('products')
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions(['1:1', '4:3'])
                            ->panelLayout('grid'),
                    ]),

                Section::make('Giá bán & tồn kho')->icon('heroicon-o-banknotes')->columns(2)->schema([
                    TextInput::make('price')
                        ->label('Giá bán')
                        ->numeric()->minValue(0)->step(1000)
                        ->suffix('đ')
                        ->required()
                        ->helperText('Nếu có nhiều quy cách bên dưới, giá từng quy cách sẽ được dùng.'),
                    TextInput::make('compare_at_price')
                        ->label('Giá gốc (gạch ngang)')
                        ->numeric()->minValue(0)->step(1000)
                        ->suffix('đ')
                        ->helperText('Để trống nếu không giảm giá.')
                        ->gt('price'),
                    TextInput::make('unit')
                        ->label('Đơn vị tính')
                        ->required()
                        ->default('kg')
                        ->datalist(['kg', 'con', 'túi', 'khay', 'hộp', 'bao', 'bộ']),
                    TextInput::make('min_order_quantity')
                        ->label('Số lượng mua tối thiểu')
                        ->numeric()->minValue(1)->default(1),
                    TextInput::make('sku')
                        ->label('Mã sản phẩm (SKU)')
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Toggle::make('track_stock')
                        ->label('Quản lý tồn kho')
                        ->helperText('Tắt nếu sản phẩm luôn có sẵn.')
                        ->default(true)
                        ->live()
                        ->inline(false),
                    TextInput::make('stock_quantity')
                        ->label('Số lượng tồn kho')
                        ->numeric()->default(0)
                        ->visible(fn (Get $get) => $get('track_stock')),
                    TextInput::make('low_stock_threshold')
                        ->label('Cảnh báo sắp hết khi còn')
                        ->numeric()->default(5)
                        ->visible(fn (Get $get) => $get('track_stock')),
                ]),

                Section::make('Quy cách / Biến thể')->icon('heroicon-o-squares-plus')
                    ->description('Ví dụ: "Túi 100 con", "Túi 500 con", "Khay 500 g". Bỏ trống nếu sản phẩm chỉ có một loại.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('variants')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->table([
                                TableColumn::make('Tên quy cách')->markAsRequired(),
                                TableColumn::make('Giá bán (đ)')->markAsRequired(),
                                TableColumn::make('Giá gốc (đ)'),
                                TableColumn::make('Tồn kho'),
                                TableColumn::make('Mặc định')->width('90px'),
                                TableColumn::make('Bán')->width('70px'),
                            ])
                            ->schema([
                                TextInput::make('name')->required()->placeholder('Túi 100 con'),
                                TextInput::make('price')->numeric()->required()->minValue(0),
                                TextInput::make('compare_at_price')->numeric()->minValue(0),
                                TextInput::make('stock_quantity')->numeric()->default(0),
                                Toggle::make('is_default'),
                                Toggle::make('is_active')->default(true),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Thêm quy cách'),
                    ]),

                Section::make('Mô tả chi tiết')->icon('heroicon-o-document-text')->schema([
                    RichEditor::make('description')
                        ->hiddenLabel()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('products/content')
                        ->fileAttachmentsVisibility('public')
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'link'],
                            ['h2', 'h3'],
                            ['bulletList', 'orderedList', 'blockquote'],
                            ['table', 'attachFiles'],
                            ['undo', 'redo'],
                        ]),
                ]),

                Section::make('Thông số kỹ thuật')->icon('heroicon-o-table-cells')->collapsible()->collapsed()->schema([
                    Repeater::make('specifications')
                        ->hiddenLabel()
                        ->table([TableColumn::make('Thông số'), TableColumn::make('Giá trị')])
                        ->schema([
                            TextInput::make('label')->placeholder('Kích cỡ')->required(),
                            TextInput::make('value')->placeholder('120–150 con/kg')->required(),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Thêm thông số'),
                ]),
            ]),

            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Trạng thái')->schema([
                    Toggle::make('is_active')->label('Đang bán (hiện trên website)')->default(true),
                    Toggle::make('is_featured')->label('Sản phẩm nổi bật (hiện ở trang chủ)'),
                    DateTimePicker::make('published_at')
                        ->label('Hẹn giờ đăng')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->helperText('Để trống để đăng ngay.'),
                ]),
                Section::make('Phân loại')->schema([
                    Select::make('category_id')
                        ->label('Danh mục')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([TextInput::make('name')->label('Tên danh mục')->required()]),
                    TextInput::make('sort_order')->label('Thứ tự hiển thị')->numeric()->default(0)->helperText('Số nhỏ hiện trước.'),
                ]),
                SeoFields::section('name', 'short_description', 'san-pham'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'variants']))
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('images')->label('Ảnh')->disk('public')->limit(1)->imageHeight(48)->square()
                    ->defaultImageUrl(asset('images/placeholder-product.svg')),
                TextColumn::make('name')->label('Tên sản phẩm')->searchable(['name', 'sku'])->sortable()->weight('semibold')->wrap()
                    ->description(fn (Product $record) => $record->category?->name),
                TextColumn::make('price')->label('Giá')->sortable()
                    ->state(fn (Product $record) => $record->displayPrice())->money()
                    ->description(fn (Product $record) => $record->variants->count() > 1 ? $record->variants->count().' quy cách' : null),
                TextColumn::make('stock_quantity')->label('Tồn kho')->sortable()->alignCenter()
                    ->state(fn (Product $record) => ! $record->track_stock ? '∞' : ($record->variants->isNotEmpty() ? $record->variants->sum('stock_quantity') : $record->stock_quantity))
                    ->badge()
                    ->color(fn (Product $record, $state) => $state === '∞' ? 'gray' : ((int) $state <= 0 ? 'danger' : ((int) $state <= $record->low_stock_threshold ? 'warning' : 'success'))),
                TextColumn::make('sold_count')->label('Đã bán')->numeric()->sortable()->toggleable(),
                TextColumn::make('rating_average')->label('Đánh giá')->formatStateUsing(fn ($state, Product $record) => $record->rating_count ? number_format($state, 1).' ★ ('.$record->rating_count.')' : '—')->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_featured')->label('Nổi bật'),
                ToggleColumn::make('is_active')->label('Đang bán'),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Danh mục')->relationship('category', 'name')->preload(),
                TernaryFilter::make('is_active')->label('Đang bán'),
                TernaryFilter::make('is_featured')->label('Nổi bật'),
                Filter::make('low_stock')->label('Sắp hết hàng / hết hàng')
                    ->query(fn (Builder $query) => $query->where('track_stock', true)->where(fn (Builder $q) => $q
                        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->whereDoesntHave('variants')
                        ->orWhereHas('variants', fn (Builder $v) => $v->where('stock_quantity', '<=', 5)))),
                TrashedFilter::make()->label('Thùng rác'),
            ])
            ->recordActions([
                Action::make('view_site')->label('Xem')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Product $record) => $record->url())->openUrlInNewTab(),
                EditAction::make(),
                ActionGroup::make([
                    Action::make('stock')->label('Nhập kho nhanh')->icon('heroicon-o-archive-box-arrow-down')
                        ->visible(fn (Product $record) => $record->track_stock && $record->variants->isEmpty())
                        ->schema([TextInput::make('add')->label('Số lượng nhập thêm')->numeric()->required()->helperText('Nhập số âm để trừ kho.')])
                        ->action(fn (Product $record, array $data) => $record->increment('stock_quantity', (int) $data['add']))
                        ->successNotificationTitle('Đã cập nhật tồn kho'),
                    ReplicateAction::make()->label('Nhân bản')
                        ->excludeAttributes(['slug', 'sku', 'sold_count', 'views_count', 'rating_average', 'rating_count'])
                        ->mutateRecordDataUsing(fn (array $data) => [...$data, 'name' => $data['name'].' (bản sao)', 'is_active' => false])
                        ->successRedirectUrl(fn (Model $replica) => self::getUrl('edit', ['record' => $replica])),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')->label('Bật bán')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')->label('Ngừng bán')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có sản phẩm nào')
            ->emptyStateDescription('Bấm "Tạo sản phẩm" để đăng sản phẩm đầu tiên.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Giá' => money($record->price), 'Tồn kho' => $record->track_stock ? $record->stock_quantity : '∞'];
    }

    public static function getNavigationBadge(): ?string
    {
        $low = Product::query()->where('is_active', true)->where('track_stock', true)
            ->whereDoesntHave('variants')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();

        return $low > 0 ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Sản phẩm sắp hết hàng';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
