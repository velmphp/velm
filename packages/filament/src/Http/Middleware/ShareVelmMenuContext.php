<?php

declare(strict_types=1);

namespace Velm\Filament\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Velm\Environment;
use Velm\Filament\Support\MenuActivePath;
use Velm\Views\Menu\MenuLayoutContext;
use Velm\Views\Menu\MenuTreeBuilder;

final class ShareVelmMenuContext
{
    public function __construct(
        private readonly MenuTreeBuilder $treeBuilder,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Filament::getCurrentPanel()?->getId() !== 'velm' || ! app()->bound(Environment::class)) {
            return $next($request);
        }

        $currentPath = MenuActivePath::forRequest($request);
        $tree = $this->treeBuilder->build(app(Environment::class), $currentPath);
        $layout = config('velm.menu_layout');
        $context = MenuLayoutContext::forTree(
            $tree,
            $currentPath,
            is_string($layout) ? $layout : null,
        );

        View::share('velmMenu', $context);

        return $next($request);
    }
}
