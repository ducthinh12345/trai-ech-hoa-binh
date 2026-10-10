<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Providers\AppServiceProvider;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTestimonials extends ManageRecords
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Thêm cảm nhận')->icon('heroicon-o-plus')->after(fn () => AppServiceProvider::flushStorefrontCache())];
    }
}
