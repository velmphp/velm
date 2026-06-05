<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Dashboard\DashboardService;
use Velm\Environment;

final class DashboardPage extends VelmShellPage
{
    protected static ?string $slug = 'dashboard';

    public function getTitle(): string|Htmlable
    {
        return __('Dashboard');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function widgets(): array
    {
        $env = app(Environment::class);

        /** @var list<string> $roots */
        $roots = config('velm.addon_paths', []);

        return (new DashboardService)->visibleWidgets($env, $roots);
    }

    public function render()
    {
        return view('velm-ui::pages.dashboard');
    }
}
