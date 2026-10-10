<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StoreStats;
use App\Filament\Widgets\TopProducts;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tổng quan';

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }

    public function getWidgets(): array
    {
        return [
            StoreStats::class,
            RevenueChart::class,
            OrderStatusChart::class,
            LatestOrders::class,
            TopProducts::class,
            LowStockProducts::class,
        ];
    }
}
