<?php

declare(strict_types=1);

namespace Velm\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Velm\Environment;
use Velm\Admin\Support\AppsCatalogMenuContext;
use Velm\Admin\Support\CompanyBranding;
use Velm\Admin\Support\MenuActivePath;
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
        if (
            (! $request->is('velm*') && ! $request->is('web/files*') && ! $request->is('web/workflow*'))
            || ! app()->bound(Environment::class)
        ) {
            return $next($request);
        }

        $env = app(Environment::class);

        if (AppsCatalogMenuContext::matches($request)) {
            View::share('velmMenu', AppsCatalogMenuContext::build($request, $env));
        } else {
            $currentPath = MenuActivePath::forRequest($request);
            $tree = $this->treeBuilder->build($env, $currentPath);
            $layout = config('velm.menu_layout');
            View::share('velmMenu', MenuLayoutContext::forTree(
                $tree,
                $currentPath,
                is_string($layout) ? $layout : null,
            ));
        }
        View::share('velmShell', array_merge(
            self::shellContext($env),
            CompanyBranding::forEnvironment($env),
        ));

        return $next($request);
    }

    /**
     * @return array{
     *     companies: list<array{id: int, name: string}>,
     *     current_company_id: int|null,
     *     current_company_name: string,
     *     allow_all_companies: bool,
     * }
     */
    private static function shellContext(Environment $env): array
    {
        $currentCompanyId = $env->companyId();
        $currentCompanyName = '';
        $companies = [];
        $allowedIds = array_flip($env->allowedCompanyIds());

        if (! $env->registry->has('res.company')) {
            return [
                'companies' => [],
                'current_company_id' => $currentCompanyId,
                'current_company_name' => $currentCompanyName,
                'allow_all_companies' => $env->allowsAllCompaniesMode(),
            ];
        }

        if ($currentCompanyId !== null) {
            $active = $env->withAclBypass(
                fn () => $env->model('res.company')->search([['id', '=', $currentCompanyId]], limit: 1)->read(['name']),
            );

            if ($active !== []) {
                $currentCompanyName = (string) ($active[0]['name'] ?? '');
            }
        }

        $rows = $env->withAclBypass(
            fn (): array => $env->model('res.company')->search()->read(['id', 'name']),
        );

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $name = (string) ($row['name'] ?? '');

            if ($id <= 0 || $name === '') {
                continue;
            }

            if ($allowedIds !== [] && ! isset($allowedIds[$id]) && ! $env->isSuperuser()) {
                continue;
            }

            $companies[] = ['id' => $id, 'name' => $name];
        }

        return [
            'companies' => $companies,
            'current_company_id' => $currentCompanyId,
            'current_company_name' => $currentCompanyName,
            'allow_all_companies' => $env->allowsAllCompaniesMode(),
        ];
    }
}
