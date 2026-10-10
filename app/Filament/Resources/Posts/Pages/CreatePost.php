<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Providers\AppServiceProvider;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function afterCreate(): void
    {
        AppServiceProvider::flushStorefrontCache();
    }
}
