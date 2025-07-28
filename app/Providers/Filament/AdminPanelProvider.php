<?php

namespace App\Providers\Filament;

use Illuminate\Support\Facades\Auth;
use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Resources\InvoiceResource;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\FinancialOverview;
use App\Filament\Widgets\ComprehensiveFinancialOverview;
use App\Filament\Widgets\CashFlowChart;
use App\Filament\Widgets\VendorPerformanceChart;
use App\Filament\Widgets\RevenueBreakdownChart;
use App\Filament\Widgets\ServiceProfitabilityTable;
use App\Filament\Widgets\OutstandingPaymentsTable;
use Filament\Widgets;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Enums\MaxWidth;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('logo/logo.png'))
            ->brandLogoHeight('3.5rem')
            ->databaseNotifications()
            ->plugin(FilamentSpatieLaravelBackupPlugin::make())
            ->brandName('Travelify - Travel Agency Management')
            ->maxContentWidth(MaxWidth::Full)
            ->login()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->globalSearch() // Enable global search
            ->navigationItems([
                NavigationItem::make('New Booking')
                    ->url(fn(): string => InvoiceResource::getUrl('create'))
                    ->icon('heroicon-o-plus-circle')
                    ->badge('Quick')
                    ->sort(1),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Priority 1: Most critical financial overview
                ComprehensiveFinancialOverview::class,

                // Priority 2-4: Key analytical charts  
                CashFlowChart::class,
                VendorPerformanceChart::class,
                RevenueBreakdownChart::class,

                // Priority 5-6: Detailed tables
                ServiceProfitabilityTable::class,
                OutstandingPaymentsTable::class,

                // Default Filament widgets
                Widgets\AccountWidget::class,

                // Other widgets (lower priority)
                \App\Filament\Widgets\FutureToursChart::class,
                \App\Filament\Widgets\CurrentDayToursTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('My Profile')
                    ->setNavigationLabel('My Profile')
                    ->setNavigationGroup('Account')
                    ->setIcon('heroicon-o-user')
                    ->setSort(10)
                    ->canAccess(fn() => Auth::check())
                    ->shouldRegisterNavigation(true)
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowSanctumTokens(false)
                    ->shouldShowBrowserSessionsForm(false),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
