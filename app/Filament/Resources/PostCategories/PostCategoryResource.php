<?php

namespace App\Filament\Resources\PostCategories;

use App\Filament\Resources\PostCategories\Pages\ManagePostCategories;
use App\Filament\Support\SeoFields;
use App\Models\PostCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung website';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'chuyên mục';

    protected static ?string $pluralModelLabel = 'Chuyên mục bài viết';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Tên chuyên mục')->required(),
            TextInput::make('slug')->label('Đường dẫn')->prefix('/tin-tuc/chuyen-muc/')->unique(ignoreRecord: true)->helperText('Để trống để tự tạo.'),
            Textarea::make('description')->label('Mô tả')->rows(2),
            TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(0),
            SeoFields::section('name', 'description', 'tin-tuc/chuyen-muc'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('posts'))
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Tên')->weight('semibold'),
                TextColumn::make('posts_count')->label('Số bài')->badge(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePostCategories::route('/')];
    }
}
