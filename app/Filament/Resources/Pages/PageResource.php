<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\SeoFields;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung website';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'trang';

    protected static ?string $pluralModelLabel = 'Trang tĩnh (chính sách...)';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(['lg' => 2])->schema([
                TextInput::make('title')->label('Tiêu đề')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')->label('Đường dẫn')->prefix('/trang/')->unique(ignoreRecord: true),
                RichEditor::make('content')->label('Nội dung')
                    ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('pages')->fileAttachmentsVisibility('public'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Hiển thị')->schema([
                    Toggle::make('is_published')->label('Công khai')->default(true),
                    Toggle::make('show_in_footer')->label('Hiện link ở chân trang')->default(true),
                    TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(0),
                ]),
                SeoFields::section('title', 'content', 'trang'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->weight('semibold')->description(fn (Page $r) => '/trang/'.$r->slug),
                ToggleColumn::make('show_in_footer')->label('Ở chân trang'),
                ToggleColumn::make('is_published')->label('Công khai'),
                TextColumn::make('updated_at')->label('Cập nhật')->since(),
            ])
            ->recordActions([
                Action::make('view')->label('Xem')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(fn (Page $r) => $r->url())->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
