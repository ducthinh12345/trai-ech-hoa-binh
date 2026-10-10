<?php

namespace App\Providers;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /* Mặc định toàn bộ bảng/infolist trong admin hiển thị tiền VND và ngày kiểu Việt Nam. */
        Table::configureUsing(fn (Table $table) => $table
            ->defaultCurrency('VND')->defaultNumberLocale('vi')
            ->defaultDateDisplayFormat('d/m/Y')->defaultDateTimeDisplayFormat('d/m/Y H:i'));
        Schema::configureUsing(fn (Schema $schema) => $schema
            ->defaultCurrency('VND')->defaultNumberLocale('vi')
            ->defaultDateDisplayFormat('d/m/Y')->defaultDateTimeDisplayFormat('d/m/Y H:i'));

        Paginator::defaultView('pagination.shop');
    }

    /**
     * Xoá cache menu/footer/sitemap sau khi admin sửa danh mục, trang, sản phẩm.
     */
    public static function flushStorefrontCache(): void
    {
        foreach (['menu.categories', 'footer.pages', 'footer.categories', 'sitemap.xml'] as $key) {
            Cache::forget($key);
        }
    }
}
