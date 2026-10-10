<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'đánh giá';

    protected static ?string $pluralModelLabel = 'Đánh giá';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('rating')->label('Sao')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))->color('warning')->sortable(),
                TextColumn::make('content')->label('Nội dung')->wrap()->limit(160)->searchable()
                    ->description(fn (Review $r) => $r->reviewer_name.($r->order_id ? ' · ✓ Đã mua' : '').' · '.$r->created_at->format('d/m/Y')),
                TextColumn::make('product.name')->label('Sản phẩm')->limit(30)->url(fn (Review $r) => $r->product?->url(), true),
                TextColumn::make('admin_reply')->label('Phản hồi')->limit(40)->placeholder('Chưa phản hồi')->toggleable(),
                ToggleColumn::make('is_approved')->label('Hiển thị'),
            ])
            ->filters([
                TernaryFilter::make('is_approved')->label('Đã duyệt')->default(false)->placeholder('Tất cả'),
                SelectFilter::make('rating')->label('Số sao')->options([5 => '5 sao', 4 => '4 sao', 3 => '3 sao', 2 => '2 sao', 1 => '1 sao']),
            ])
            ->recordActions([
                Action::make('approve')->label('Duyệt')->icon('heroicon-o-check')->color('success')->button()->size('sm')
                    ->visible(fn (Review $r) => ! $r->is_approved)
                    ->action(fn (Review $r) => $r->update(['is_approved' => true])),
                Action::make('reply')->label('Phản hồi')->icon('heroicon-o-chat-bubble-left')
                    ->fillForm(fn (Review $r) => ['admin_reply' => $r->admin_reply])
                    ->schema([Textarea::make('admin_reply')->label('Phản hồi công khai từ trại')->rows(3)])
                    ->action(fn (Review $r, array $data) => $r->update($data + ['is_approved' => true])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')->label('Duyệt các đánh giá đã chọn')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->where('is_approved', false)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Đánh giá chờ duyệt';
    }

    public static function getPages(): array
    {
        return ['index' => ManageReviews::route('/')];
    }
}
