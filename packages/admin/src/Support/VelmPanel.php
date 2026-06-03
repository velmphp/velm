<?php

declare(strict_types=1);

namespace Velm\Admin\Support;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Velm\Admin\Pages\AppsPage;

final class VelmPanel
{
    public static function path(): string
    {
        return 'velm';
    }

    public static function auth(): Guard|StatefulGuard
    {
        return auth()->guard();
    }

    public static function getUrl(): string
    {
        return url('/'.self::path());
    }

    public static function homeUrl(): string
    {
        return AppsPage::getUrl();
    }

    public static function getLogoutUrl(): string
    {
        return route('velm.auth.logout');
    }

    public static function hasDarkMode(): bool
    {
        return true;
    }

    public static function hasDarkModeForced(): bool
    {
        return false;
    }

    public static function getDefaultThemeMode(): string
    {
        return 'system';
    }
}
