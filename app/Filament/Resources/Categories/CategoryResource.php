<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\SeoFields;
use App\Models\Category;
use App\Providers\AppServiceProvider;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'danh mục';

    protected static ?string $pluralModelLabel = 'Danh mục sản phẩm';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Tên danh mục')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')->label('Đường dẫn')->prefix('/danh-muc/')->unique(ignoreRecord: true),
                Select::make('parent_id')->label('Thuộc danh mục cha')->placeholder('Không (danh mục gốc)')
                    ->relationship('parent', 'name', fn ($query, ?Category $record) => $query->whereNull('parent_id')->when($record, fn ($q) => $q->whereKeyNot($record->id))),
                TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(0),
            ]),
            Textarea::make('description')->label('Mô tả')->rows(3)->helperText('Hiện ở đầu trang danh mục, tốt cho SEO.'),
            FileUpload::make('image')->label('Ảnh đại diện')->image()->disk('public')->directory('categories')->imageEditor(),
            Grid::make(2)->schema([
                Toggle::make('is_active')->label('Hiển thị')->default(true),
                Toggle::make('show_in_menu')->label('Hiện trên menu')->default(true),
            ]),
            SeoFields::section('name', 'description', 'danh-muc'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent')->withCount('products'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('public')->square()->imageHeight(44),
                TextColumn::make('name')->label('Tên')->searchable()->weight('semibold')
                    ->description(fn (Category $record) => $record->parent ? '↳ thuộc '.$record->parent->name : null),
                TextColumn::make('products_count')->label('Số sản phẩm')->badge()->alignCenter(),
                ToggleColumn::make('show_in_menu')->label('Trên menu')->afterStateUpdated(fn () => AppServiceProvider::flushStorefrontCache()),
                ToggleColumn::make('is_active')->label('Hiển thị')->afterStateUpdated(fn () => AppServiceProvider::flushStorefrontCache()),
            ])
            ->recordActions([
                EditAction::make()->after(fn () => AppServiceProvider::flushStorefrontCache()),
                DeleteAction::make()->after(fn () => AppServiceProvider::flushStorefrontCache())
                    ->modalDescription('Sản phẩm trong danh mục sẽ không bị xoá, chỉ bỏ phân loại.'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
