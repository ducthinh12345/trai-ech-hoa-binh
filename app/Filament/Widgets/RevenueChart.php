<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Doanh thu & số đơn';

    protected ?string $description = 'Không tính đơn đã huỷ';

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 2];

    protected ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => '7 ngày', '30' => '30 ngày', '90' => '90 ngày'];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $rows = Order::query()->revenue()
            ->where('created_at', '>=', today()->subDays($days - 1))
            ->selectRaw('DATE(created_at) as d, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('d')->get()->keyBy('d');

        $dates = collect(range($days - 1, 0))->map(fn ($i) => today()->subDays($i));

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu (nghìn đ)',
                    'data' => $dates->map(fn ($d) => round(($rows[$d->toDateString()]->revenue ?? 0) / 1000))->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22,163,74,0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                    'cubicInterpolationMode' => 'monotone',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Số đơn',
                    'data' => $dates->map(fn ($d) => (int) ($rows[$d->toDateString()]->orders ?? 0))->all(),
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(234,179,8,0.55)',
                    'borderRadius' => 4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $dates->map(fn ($d) => $d->format('d/m'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['position' => 'left', 'beginAtZero' => true],
                'y1' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false], 'ticks' => ['precision' => 0]],
            ],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
