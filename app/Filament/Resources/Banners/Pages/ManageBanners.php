<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Providers\AppServiceProvider;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBanners extends ManageRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm banner')->icon('heroicon-o-plus')->after(fn () => AppServiceProvider::flushStorefrontCache())];
    }
}
