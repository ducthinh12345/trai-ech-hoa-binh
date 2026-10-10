<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Trạng thái đơn (30 ngày)';

    protected ?string $maxHeight = '300px';

    private const COLORS = [
        'pending' => '#f59e0b', 'confirmed' => '#0ea5e9', 'processing' => '#16a34a',
        'shipping' => '#8b5cf6', 'completed' => '#15803d', 'cancelled' => '#ef4444',
    ];

    protected function getData(): array
    {
        $counts = Order::query()->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $statuses = collect(OrderStatus::cases())->filter(fn ($s) => ($counts[$s->value] ?? 0) > 0)->values();

        return [
            'datasets' => [[
                'data' => $statuses->map(fn ($s) => (int) $counts[$s->value])->all(),
                'backgroundColor' => $statuses->map(fn ($s) => self::COLORS[$s->value])->all(),
                'borderWidth' => 0,
            ]],
            'labels' => $statuses->map(fn ($s) => $s->getLabel())->all(),
        ];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']], 'cutout' => '62%', 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
