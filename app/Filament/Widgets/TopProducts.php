<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopProducts extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 1];

    /**
     * Truy vấn trên bảng products (mỗi dòng có khoá chính thật) với tổng con từ order_items:
     * Filament luôn thêm ORDER BY khoá chính, nên GROUP BY trên order_items bị MySQL ONLY_FULL_GROUP_BY từ chối.
     */
    public function table(Table $table): Table
    {
        $recentPaidItems = fn (Builder $query) => $query->whereHas('order', fn (Builder $order) => $order
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subDays(30)));

        return $table
            ->heading('Bán chạy 30 ngày')
            ->query(fn () => Product::query()
                ->whereHas('orderItems', $recentPaidItems)
                ->withSum(['orderItems as revenue' => $recentPaidItems], 'line_total')
                ->withSum(['orderItems as qty' => $recentPaidItems], 'quantity')
                ->orderByDesc('revenue')
                ->limit(6))
            ->paginated(false)
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label('Sản phẩm')->limit(34)->weight('semibold')
                    ->description(fn (Product $record) => 'Đã bán '.(int) $record->qty.' · '.money($record->revenue)),
            ]);
    }
}
