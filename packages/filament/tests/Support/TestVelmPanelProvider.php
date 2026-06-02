<?php

declare(strict_types=1);

namespace Velm\Filament\Tests\Support;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Velm\Filament\Pages\CompanyListPage;
use Velm\Filament\Pages\CreateCompanyPage;
use Velm\Filament\Pages\CreatePartnerPage;
use Velm\Filament\Pages\EditCompanyPage;
use Velm\Filament\Pages\EditPartnerPage;
use Velm\Filament\Pages\PartnerListPage;

/**
 * Panel without auth — used in package tests only.
 */
final class TestVelmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('velm')
            ->path('velm')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                Dashboard::class,
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
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
