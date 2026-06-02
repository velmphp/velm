<?php

declare(strict_types=1);

namespace Velm\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Velm\Filament\Http\Middleware\ShareVelmMenuContext;
use Velm\Filament\Pages\AppsPage;
use Velm\Filament\Pages\CompanyListPage;
use Velm\Filament\Pages\CreateCompanyPage;
use Velm\Filament\Pages\CreatePartnerPage;
use Velm\Filament\Pages\EditCompanyPage;
use Velm\Filament\Pages\EditPartnerPage;
use Velm\Filament\Pages\PartnerListPage;

final class VelmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('velm')
            ->path('velm')
            ->login()
            ->navigation(false)
            ->homeUrl(fn (): string => AppsPage::getUrl())
            ->authenticatedTenantRoutes(function (): void {
                // Filament's default home redirect uses navigation items; with
                // navigation(false) it loops back to /velm. Register home first.
                Route::get('/', static function (): RedirectResponse {
                    $home = AppsPage::getUrl(panel: 'velm');

                    return redirect()->to($home);
                })->name('home');
            })
            ->colors([
                'primary' => Color::Amber,
            ])
            ->assets([
                Css::make('velm-shell', __DIR__.'/../resources/css/velm-shell.css'),
            ], 'velm-filament')
            ->pages([
                AppsPage::class,
                CompanyListPage::class,
                CreateCompanyPage::class,
                EditCompanyPage::class,
                PartnerListPage::class,
                CreatePartnerPage::class,
                EditPartnerPage::class,
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
                ShareVelmMenuContext::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
