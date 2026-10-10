<?php

namespace App\Filament\Resources\ShippingMethods;

use App\Filament\Resources\ShippingMethods\Pages\ManageShippingMethods;
use App\Models\ShippingMethod;
use App\Support\Provinces;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'phương thức vận chuyển';

    protected static ?string $pluralModelLabel = 'Phí vận chuyển';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Tên hiển thị cho khách')->required()->placeholder('VD: Giao nhanh nội tỉnh'),
            TextInput::make('description')->label('Mô tả ngắn')->placeholder('VD: Xe của trại giao trong ngày'),
            Grid::make(3)->schema([
                TextInput::make('fee')->label('Phí ship')->numeric()->suffix('đ')->required()->default(0),
                TextInput::make('free_from_amount')->label('Miễn phí khi đơn từ')->numeric()->suffix('đ')->helperText('Để trống = không miễn phí'),
                TextInput::make('estimated_days')->label('Thời gian giao')->placeholder('1–3 ngày'),
            ]),
            Select::make('province_codes')->label('Áp dụng cho tỉnh/thành')->multiple()->searchable()
                ->options(Provinces::options())
                ->placeholder('Để trống = áp dụng toàn quốc')
                ->helperText('Nếu một tỉnh có phương thức riêng, khách ở tỉnh đó chỉ thấy phương thức riêng.'),
            Grid::make(2)->schema([
                TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(0),
                Toggle::make('is_active')->label('Đang dùng')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Tên')->weight('semibold')->description(fn (ShippingMethod $r) => $r->description),
                TextColumn::make('fee')->label('Phí')->money(),
                TextColumn::make('free_from_amount')->label('Miễn phí từ')->money()->placeholder('—'),
                TextColumn::make('province_codes')->label('Khu vực')
                    ->state(fn (ShippingMethod $r) => $r->isNationwide() ? 'Toàn quốc' : collect($r->province_codes)->map(fn ($c) => Provinces::name($c))->implode(', '))
                    ->badge()->color(fn (ShippingMethod $r) => $r->isNationwide() ? 'gray' : 'info'),
                TextColumn::make('estimated_days')->label('Thời gian'),
                ToggleColumn::make('is_active')->label('Bật'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageShippingMethods::route('/')];
    }
}
