<?php

namespace App\Filament\Resources\PostCategories\Pages;

use App\Filament\Resources\PostCategories\PostCategoryResource;
use App\Providers\AppServiceProvider;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePostCategories extends ManageRecords
{
    protected static string $resource = PostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm chuyên mục')->icon('heroicon-o-plus')->after(fn () => AppServiceProvider::flushStorefrontCache())];
    }
}
