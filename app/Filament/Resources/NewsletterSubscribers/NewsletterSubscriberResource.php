<?php

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Filament\Resources\NewsletterSubscribers\Pages\ManageNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Khách hàng';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'người đăng ký';

    protected static ?string $pluralModelLabel = 'Đăng ký nhận tin';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('copy')->label('Sao chép danh sách email')->icon('heroicon-o-clipboard-document')->color('gray')
                    ->action(fn ($livewire) => $livewire->js('navigator.clipboard.writeText('.json_encode(NewsletterSubscriber::query()->where('is_active', true)->pluck('email')->implode(', ')).')'))
                    ->successNotificationTitle('Đã sao chép, dán vào công cụ gửi email của bạn'),
            ])
            ->columns([
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('created_at')->label('Ngày đăng ký')->dateTime()->sortable(),
                ToggleColumn::make('is_active')->label('Nhận tin'),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNewsletterSubscribers::route('/')];
    }
}
