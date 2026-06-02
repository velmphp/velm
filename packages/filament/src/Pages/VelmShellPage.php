<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Filament\Pages\Page;

abstract class VelmShellPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'velm-filament::layouts.velm-app';
}
