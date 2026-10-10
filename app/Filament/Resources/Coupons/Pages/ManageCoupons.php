<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use App\Providers\AppServiceProvider;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCoupons extends ManageRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tạo mã giảm giá')->icon('heroicon-o-plus')->after(fn () => AppServiceProvider::flushStorefrontCache())];
    }
}
