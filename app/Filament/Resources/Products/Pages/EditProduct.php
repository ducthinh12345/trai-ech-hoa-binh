<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Providers\AppServiceProvider;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_site')->label('Xem trên website')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => $this->record->url())->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        AppServiceProvider::flushStorefrontCache();
    }
}
