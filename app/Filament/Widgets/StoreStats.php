<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\ContactMessage;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StoreStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = $this->revenueBetween(today(), now());
        $yesterday = $this->revenueBetween(today()->subDay(), today()->subDay()->endOfDay());
        $month = $this->revenueBetween(now()->startOfMonth(), now());
        $lastMonth = $this->revenueBetween(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow());

        $pending = Order::query()->where('status', OrderStatus::Pending)->count();
        $shipping = Order::query()->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipping])->count();

        return [
            Stat::make('Doanh thu hôm nay', money($today))
                ->description($this->compare($today, $yesterday, 'hôm qua'))
                ->descriptionIcon($today >= $yesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($today >= $yesterday ? 'success' : 'danger')
                ->chart($this->dailyRevenue(7)),
            Stat::make('Doanh thu tháng này', money($month))
                ->description($this->compare($month, $lastMonth, 'cùng kỳ tháng trước'))
                ->descriptionIcon($month >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($month >= $lastMonth ? 'success' : 'danger')
                ->chart($this->dailyRevenue(30)),
            Stat::make('Đơn chờ xác nhận', $pending)
                ->description($pending ? 'Gọi khách xác nhận ngay' : 'Không có đơn tồn')
                ->descriptionIcon('heroicon-m-phone')
                ->color($pending ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.orders.index', ['tab' => 'pending'])),
            Stat::make('Đơn đang xử lý / giao', $shipping)
                ->description(ContactMessage::query()->where('is_read', false)->count().' tin nhắn liên hệ chưa đọc')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('info'),
        ];
    }

    private function revenueBetween(Carbon $from, Carbon $to): int
    {
        return (int) Order::query()->revenue()->whereBetween('created_at', [$from, $to])->sum('total');
    }

    private function compare(int $current, int $previous, string $label): string
    {
        if ($previous === 0) {
            return $current > 0 ? 'Tăng so với '.$label : 'Chưa có doanh thu';
        }

        $pct = round(($current - $previous) / $previous * 100);

        return ($pct >= 0 ? '+' : '').$pct.'% so với '.$label;
    }

    /**
     * @return array<int, int>
     */
    private function dailyRevenue(int $days): array
    {
        $rows = Order::query()->revenue()
            ->where('created_at', '>=', today()->subDays($days - 1))
            ->selectRaw('DATE(created_at) as d, SUM(total) as t')
            ->groupBy('d')->pluck('t', 'd');

        return collect(range($days - 1, 0))->map(fn ($i) => (int) ($rows[today()->subDays($i)->toDateString()] ?? 0))->all();
    }
}
