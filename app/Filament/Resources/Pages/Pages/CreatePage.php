<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Providers\AppServiceProvider;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function afterCreate(): void
    {
        AppServiceProvider::flushStorefrontCache();
    }
}
