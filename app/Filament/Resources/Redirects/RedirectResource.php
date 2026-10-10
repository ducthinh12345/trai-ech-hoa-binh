<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'chuyển hướng';

    protected static ?string $pluralModelLabel = 'Chuyển hướng URL (SEO)';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('from_path')->label('Đường dẫn cũ')->required()->prefix(rtrim(config('app.url'), '/'))->placeholder('/san-pham/ten-cu')
                ->unique(ignoreRecord: true)->helperText('Hệ thống tự tạo mục này khi bạn đổi đường dẫn sản phẩm/bài viết.'),
            TextInput::make('to_url')->label('Chuyển tới')->required()->placeholder('/san-pham/ten-moi hoặc https://...'),
            Select::make('status_code')->label('Loại')->options([301 => '301 – Chuyển vĩnh viễn (khuyên dùng)', 302 => '302 – Tạm thời'])->default(301)->required(),
            Toggle::make('is_active')->label('Đang bật')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Từ')->searchable()->fontFamily('mono'),
                TextColumn::make('to_url')->label('Đến')->searchable()->fontFamily('mono')->limit(50),
                TextColumn::make('status_code')->label('Mã')->badge(),
                TextColumn::make('hits')->label('Lượt chuyển')->numeric()->sortable(),
                ToggleColumn::make('is_active')->label('Bật'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
