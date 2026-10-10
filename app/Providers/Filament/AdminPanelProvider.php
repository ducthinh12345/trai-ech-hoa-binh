<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Cùng tông với bảng brand của storefront (resources/css/app.css) nhưng đẩy tối một nấc:
     * nút Filament dùng nấc 600, phải đủ tối để chữ trắng đạt tương phản.
     */
    private const BRAND_GREEN = [
        50 => 'oklch(0.97 0.03 150)',
        100 => 'oklch(0.94 0.05 150)',
        200 => 'oklch(0.88 0.09 150)',
        300 => 'oklch(0.80 0.13 150)',
        400 => 'oklch(0.66 0.15 150)',
        500 => 'oklch(0.55 0.15 151)',
        600 => 'oklch(0.47 0.13 152)',
        700 => 'oklch(0.41 0.11 153)',
        800 => 'oklch(0.35 0.09 155)',
        900 => 'oklch(0.28 0.07 157)',
        950 => 'oklch(0.20 0.05 160)',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->profile()
            ->brandName('Trại Ếch Hòa Bình')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.svg'))
            ->font('Be Vietnam Pro')
            ->colors([
                'primary' => self::BRAND_GREEN,
                'gray' => Color::Stone,
                'violet' => Color::Violet,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->unsavedChangesAlerts()
            ->navigationGroups([
                NavigationGroup::make('Bán hàng'),
                NavigationGroup::make('Sản phẩm'),
                NavigationGroup::make('Nội dung website'),
                NavigationGroup::make('Khách hàng'),
                NavigationGroup::make('Hệ thống')->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('Xem website')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(100),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
