<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\ManageTestimonials;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung website';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'cảm nhận';

    protected static ?string $pluralModelLabel = 'Cảm nhận khách hàng';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('customer_name')->label('Tên khách')->required(),
                TextInput::make('customer_title')->label('Giới thiệu ngắn')->placeholder('Hộ nuôi 20 bể – Phú Thọ'),
            ]),
            Textarea::make('content')->label('Nội dung cảm nhận')->rows(4)->required(),
            ToggleButtons::make('rating')->label('Số sao')->inline()->default(5)->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])->icons(array_fill_keys(range(1, 5), 'heroicon-s-star')),
            FileUpload::make('avatar')->label('Ảnh đại diện')->image()->avatar()->disk('public')->directory('testimonials')->imageEditor(),
            Toggle::make('is_active')->label('Hiển thị')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('avatar')->label('')->disk('public')->circular()->defaultImageUrl(asset('images/logo.svg')),
                TextColumn::make('customer_name')->label('Khách')->weight('semibold')->description(fn (Testimonial $r) => $r->customer_title),
                TextColumn::make('content')->label('Nội dung')->limit(90)->wrap(),
                ToggleColumn::make('is_active')->label('Hiện'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTestimonials::route('/')];
    }
}
