<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Khách hàng';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'liên hệ';

    protected static ?string $pluralModelLabel = 'Tin nhắn liên hệ';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label('Họ tên'),
            TextEntry::make('phone')->label('Điện thoại')->copyable()->url(fn (ContactMessage $r) => 'tel:'.$r->phone),
            TextEntry::make('email')->label('Email')->placeholder('—')->copyable(),
            TextEntry::make('subject')->label('Chủ đề')->placeholder('—'),
            TextEntry::make('message')->label('Nội dung')->columnSpanFull(),
            TextEntry::make('created_at')->label('Gửi lúc')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_read')->label('')->boolean()->trueIcon('heroicon-o-envelope-open')->falseIcon('heroicon-s-envelope')->falseColor('danger')->trueColor('gray'),
                TextColumn::make('name')->label('Người gửi')->searchable(['name', 'phone'])->weight(fn (ContactMessage $r) => $r->is_read ? null : 'bold')
                    ->description(fn (ContactMessage $r) => $r->phone),
                TextColumn::make('subject')->label('Chủ đề')->badge()->color('gray'),
                TextColumn::make('message')->label('Nội dung')->limit(70)->wrap(),
                TextColumn::make('created_at')->label('Thời gian')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_read')->label('Đã đọc')])
            ->recordActions([
                ViewAction::make()->label('Đọc')->after(fn (ContactMessage $r) => $r->update(['is_read' => true])),
                Action::make('call')->label('Gọi')->icon('heroicon-o-phone')->color('success')->url(fn (ContactMessage $r) => 'tel:'.$r->phone),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = ContactMessage::query()->where('is_read', false)->count();

        return $unread ? (string) $unread : null;
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}
