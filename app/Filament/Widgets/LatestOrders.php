<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Đơn hàng mới nhất')
            ->query(fn () => Order::query()->latest()->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('code')->label('Mã')->weight('bold')->description(fn (Order $r) => $r->created_at->diffForHumans()),
                TextColumn::make('customer_name')->label('Khách')->description(fn (Order $r) => $r->customer_phone),
                TextColumn::make('total')->label('Tổng')->money(),
                TextColumn::make('payment_method')->label('TT')->formatStateUsing(fn (PaymentMethod $state) => $state->shortLabel()),
                TextColumn::make('status')->label('Trạng thái')->badge(),
            ])
            ->headerActions([
                Action::make('all')->label('Xem tất cả')->url(OrderResource::getUrl())->link(),
            ]);
    }
}
