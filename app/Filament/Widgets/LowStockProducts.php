<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockProducts extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sắp hết hàng — cần nhập thêm')
            ->query(fn () => Product::query()->where('is_active', true)->where('track_stock', true)->with('variants')
                ->where(fn ($q) => $q
                    ->where(fn ($p) => $p->whereDoesntHave('variants')->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
                    ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock_quantity', '<=', 5))))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('Kho hàng ổn định 👍')
            ->emptyStateDescription('Không có sản phẩm nào sắp hết.')
            ->columns([
                TextColumn::make('name')->label('Sản phẩm')->weight('semibold'),
                TextColumn::make('stock_info')->label('Tồn kho')->badge()->color('warning')
                    ->state(fn (Product $r) => $r->variants->isEmpty()
                        ? $r->stock_quantity.' '.$r->unit
                        : $r->variants->filter(fn (ProductVariant $v) => $v->stock_quantity <= 5)->map(fn (ProductVariant $v) => $v->name.': '.$v->stock_quantity)->implode(' · ')),
            ]);
    }
}
