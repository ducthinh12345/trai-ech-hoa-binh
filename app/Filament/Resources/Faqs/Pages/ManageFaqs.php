<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use App\Providers\AppServiceProvider;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm câu hỏi')->icon('heroicon-o-plus')->after(fn () => AppServiceProvider::flushStorefrontCache())];
    }
}
