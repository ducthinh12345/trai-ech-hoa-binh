<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tạo sản phẩm')->icon('heroicon-o-plus')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả'),
            'active' => Tab::make('Đang bán')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'hidden' => Tab::make('Đang ẩn')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(Product::query()->where('is_active', false)->count() ?: null),
            'featured' => Tab::make('Nổi bật')->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true)),
        ];
    }
}
