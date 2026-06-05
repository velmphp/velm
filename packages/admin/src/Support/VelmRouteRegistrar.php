<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Velm\Admin\Auth\Login;
use Velm\Admin\Http\Controllers\LogoutController;
use Velm\Admin\Http\Controllers\SwitchCompanyController;
use Velm\Framework\Http\Middleware\BindVelmEnvironment;
use Velm\Admin\Http\Middleware\ShareVelmMenuContext;
use Velm\Admin\Pages\AppsDetailPage;
use Velm\Admin\Pages\AppsPage;
use Velm\Admin\Pages\DashboardPage;
use Velm\Admin\Pages\CompanyListPage;
use Velm\Admin\Pages\CreateCompanyPage;
use Velm\Admin\Pages\CreatePartnerPage;
use Velm\Admin\Pages\EditCompanyPage;
use Velm\Admin\Pages\EditPartnerPage;
use Velm\Admin\Pages\PartnerListPage;
use Velm\Admin\Pages\StoredViewCreatePage;
use Velm\Admin\Pages\StoredViewEditPage;
use Velm\Admin\Pages\StoredViewListPage;
use Velm\Admin\Pages\StoredViewRecordPage;
use Velm\Admin\Pages\VelmShellPage;

final class VelmRouteRegistrar
{
    /** @var list<class-string<VelmShellPage>> */
    private const PAGES = [
        DashboardPage::class,
        AppsPage::class,
        AppsDetailPage::class,
        CompanyListPage::class,
        CreateCompanyPage::class,
        EditCompanyPage::class,
        PartnerListPage::class,
        CreatePartnerPage::class,
        EditPartnerPage::class,
        StoredViewListPage::class,
        StoredViewCreatePage::class,
        StoredViewEditPage::class,
        StoredViewRecordPage::class,
    ];

    public static function register(): void
    {
        $panelMiddleware = [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            BindVelmEnvironment::class,
            ShareVelmMenuContext::class,
        ];

        Route::middleware($panelMiddleware)
            ->prefix(VelmPanel::path())
            ->group(function (): void {
                Route::livewire('login', Login::class)->name('velm.auth.login');
                Route::post('logout', LogoutController::class)->name('velm.auth.logout');

                Route::middleware('auth')->group(function (): void {
                    Route::redirect('/', '/'.VelmPanel::path().'/dashboard')->name('velm.home');
                    Route::post('switch-company', SwitchCompanyController::class)->name('velm.switch-company');

                    foreach (self::PAGES as $pageClass) {
                        $route = Route::livewire($pageClass::routePath(), $pageClass)
                            ->name($pageClass::routeName());

                        if ($pageClass === StoredViewRecordPage::class || $pageClass === StoredViewEditPage::class) {
                            $route->where(['record' => '[0-9]+']);
                        }
                    }
                });
            });
    }
}
