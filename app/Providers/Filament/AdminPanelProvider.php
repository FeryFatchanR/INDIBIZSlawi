<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Pages\PageResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
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
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('CMS Website')
            ->colors([
                'primary' => Color::hex('#1769AA'),
                'gray' => Color::Slate,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Website'),
                NavigationGroup::make('Produk'),
                NavigationGroup::make('Marketing'),
                NavigationGroup::make('Media'),
                NavigationGroup::make('Leads'),
                NavigationGroup::make('Pengaturan'),
                NavigationGroup::make('Sistem')->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('Global Sections')
                    ->url(fn (): string => PageResource::getUrl('index', ['filters' => ['type' => ['value' => 'global']]]))
                    ->icon(Heroicon::OutlinedSquare3Stack3d)->group('Website')->sort(2)
                    ->visible(fn (): bool => auth()->user()?->can('pages.view') ?? false),
                NavigationItem::make('Lihat Website')
                    ->url(fn (): string => url('/'), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(99),
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
