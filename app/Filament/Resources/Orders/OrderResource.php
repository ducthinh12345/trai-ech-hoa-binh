<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'đơn hàng';

    protected static ?string $pluralModelLabel = 'Đơn hàng';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Sản phẩm trong đơn')->icon('heroicon-o-shopping-cart')->schema([
                    RepeatableEntry::make('items')->hiddenLabel()
                        ->table([
                            TableColumn::make('Sản phẩm'),
                            TableColumn::make('Đơn giá'),
                            TableColumn::make('SL')->width('70px'),
                            TableColumn::make('Thành tiền'),
                        ])
                        ->schema([
                            TextEntry::make('product_name')->weight('semibold')->belowContent(fn ($record) => $record->variant_name),
                            TextEntry::make('unit_price')->money(),
                            TextEntry::make('quantity'),
                            TextEntry::make('line_total')->money()->weight('bold'),
                        ]),
                    Grid::make(2)->schema([
                        TextEntry::make('customer_note')->label('Ghi chú của khách')->placeholder('Không có')->icon('heroicon-o-chat-bubble-bottom-center-text'),
                        Group::make([
                            TextEntry::make('subtotal')->label('Tạm tính')->money()->inlineLabel(),
                            TextEntry::make('discount_amount')->label('Giảm giá')->money()->inlineLabel()
                                ->prefix('−')->color('success')->visible(fn (Order $record) => $record->discount_amount > 0)
                                ->hint(fn (Order $record) => $record->coupon_code),
                            TextEntry::make('shipping_fee')->label('Vận chuyển')->money()->inlineLabel(),
                            TextEntry::make('total')->label('Tổng cộng')->money()->inlineLabel()->size('lg')->weight('bold')->color('primary'),
                        ]),
                    ]),
                ]),

                Section::make('Lịch sử xử lý')->icon('heroicon-o-clock')->collapsible()->schema([
                    RepeatableEntry::make('statusHistories')->hiddenLabel()
                        ->table([TableColumn::make('Thời gian'), TableColumn::make('Trạng thái'), TableColumn::make('Người xử lý'), TableColumn::make('Ghi chú')])
                        ->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('to_status')->badge(),
                            TextEntry::make('user.name')->placeholder('Hệ thống / khách'),
                            TextEntry::make('note')->placeholder('—'),
                        ]),
                ]),

                Section::make('Giao dịch thanh toán online')->icon('heroicon-o-credit-card')->collapsible()->collapsed()
                    ->visible(fn (Order $record) => $record->paymentTransactions()->exists())
                    ->schema([
                        RepeatableEntry::make('paymentTransactions')->hiddenLabel()
                            ->table([TableColumn::make('Thời gian'), TableColumn::make('Cổng'), TableColumn::make('Mã GD'), TableColumn::make('Số tiền'), TableColumn::make('Kết quả')])
                            ->schema([
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('gateway')->formatStateUsing(fn (string $state) => strtoupper($state)),
                                TextEntry::make('gateway_transaction_id')->placeholder('—')->copyable(),
                                TextEntry::make('amount')->money(),
                                TextEntry::make('status')->badge()
                                    ->color(fn (string $state) => match ($state) {
                                        'success' => 'success', 'failed' => 'danger', default => 'gray'
                                    })
                                    ->formatStateUsing(fn (string $state, $record) => match ($state) {
                                        'success' => 'Thành công', 'failed' => 'Thất bại', default => 'Đang chờ'
                                    }.($record->message ? ' – '.$record->message : '')),
                            ]),
                    ]),
            ]),

            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Trạng thái')->schema([
                    TextEntry::make('status')->label('Đơn hàng')->badge()->size('lg'),
                    TextEntry::make('payment_status')->label('Thanh toán')->badge(),
                    TextEntry::make('payment_method')->label('Hình thức')->formatStateUsing(fn (PaymentMethod $state) => $state->shortLabel()),
                    TextEntry::make('tracking_code')->label('Mã vận đơn')->placeholder('Chưa có')->copyable(),
                    TextEntry::make('created_at')->label('Đặt lúc')->dateTime()->since()->dateTimeTooltip(),
                ]),
                Section::make('Khách hàng')->icon('heroicon-o-user')->schema([
                    TextEntry::make('customer_name')->label('Họ tên')->weight('semibold'),
                    TextEntry::make('customer_phone')->label('Điện thoại')->copyable()->icon('heroicon-o-phone')
                        ->url(fn (Order $record) => 'tel:'.$record->customer_phone),
                    TextEntry::make('customer_email')->label('Email')->placeholder('—')->copyable(),
                    TextEntry::make('address')->label('Địa chỉ giao')->state(fn (Order $record) => $record->fullAddress())->copyable(),
                    TextEntry::make('shipping_method_name')->label('Vận chuyển'),
                    TextEntry::make('user.name')->label('Tài khoản')->placeholder('Khách vãng lai')
                        ->state(fn (Order $record) => $record->user ? $record->user->name.' ('.$record->user->orders()->count().' đơn)' : null),
                ]),
                Section::make('Ghi chú nội bộ')->icon('heroicon-o-pencil-square')->schema([
                    TextEntry::make('admin_note')->hiddenLabel()->placeholder('Chưa có ghi chú. Bấm "Ghi chú" phía trên để thêm.'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->recordUrl(fn (Order $record) => self::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('code')->label('Mã đơn')->searchable()->copyable()->weight('bold')
                    ->description(fn (Order $record) => $record->created_at->format('d/m H:i')),
                TextColumn::make('customer_name')->label('Khách hàng')->searchable(['customer_name', 'customer_phone'])
                    ->description(fn (Order $record) => $record->customer_phone),
                TextColumn::make('province_name')->label('Tỉnh/TP')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items_count')->label('SP')->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')->label('Tổng tiền')->money()->sortable()->weight('semibold')
                    ->summarize(Sum::make()->money()->label('Tổng')),
                TextColumn::make('payment_method')->label('Thanh toán')->formatStateUsing(fn (PaymentMethod $state) => $state->shortLabel())
                    ->description(fn (Order $record) => $record->payment_status->getLabel())
                    ->color(fn (Order $record) => $record->payment_status->getColor()),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('created_at')->label('Ngày đặt')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')->label('Thanh toán')->options(PaymentStatus::class),
                SelectFilter::make('payment_method')->label('Hình thức')->options(PaymentMethod::class),
                Filter::make('created_at')->label('Ngày đặt')
                    ->schema([
                        DatePicker::make('from')->label('Từ ngày')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Đến ngày')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ($data['from'] ?? null) ? 'Từ '.Carbon::parse($data['from'])->format('d/m/Y') : null,
                        ($data['until'] ?? null) ? 'Đến '.Carbon::parse($data['until'])->format('d/m/Y') : null,
                    ])),
            ])
            ->recordActions([
                Action::make('quick_next')
                    ->label(fn (Order $record) => self::nextStepLabel($record->status))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->button()->size('sm')
                    ->visible(fn (Order $record) => self::nextStep($record->status) !== null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Order $record) => 'Chuyển đơn '.$record->code.' sang "'.self::nextStep($record->status)?->getLabel().'"?')
                    ->action(fn (Order $record) => ViewOrder::applyTransition($record, self::nextStep($record->status))),
                ViewAction::make()->label('Chi tiết')->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm')->label('Xác nhận các đơn đã chọn')->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->filter(fn (Order $o) => $o->status === OrderStatus::Pending)
                                ->each(fn (Order $o) => ViewOrder::applyTransition($o, OrderStatus::Confirmed, notify: false));
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Đã xác nhận đơn'),
                    BulkAction::make('print')->label('In hoá đơn')->icon('heroicon-o-printer')
                        ->action(fn (Collection $records, $livewire) => $livewire->js('window.open('.json_encode(route('admin.orders.print', ['ids' => $records->pluck('id')->implode(',')])).', "_blank")')),
                ]),
            ])
            ->emptyStateHeading('Chưa có đơn hàng')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }

    /**
     * Bước tiếp theo trên quy trình chuẩn (bỏ qua nhánh huỷ) để có nút một chạm trong bảng.
     */
    public static function nextStep(OrderStatus $status): ?OrderStatus
    {
        return collect($status->allowedTransitions())->first(fn (OrderStatus $s) => $s !== OrderStatus::Cancelled);
    }

    public static function nextStepLabel(OrderStatus $status): string
    {
        return match (self::nextStep($status)) {
            OrderStatus::Confirmed => 'Xác nhận',
            OrderStatus::Processing => 'Chuẩn bị hàng',
            OrderStatus::Shipping => 'Giao hàng',
            OrderStatus::Completed => 'Hoàn thành',
            default => '',
        };
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'customer_name', 'customer_phone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Khách' => $record->customer_name.' · '.$record->customer_phone, 'Tổng' => money($record->total), 'Trạng thái' => $record->status->getLabel()];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Order::query()->where('status', OrderStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Đơn đang chờ xác nhận';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
