<?php

declare(strict_types=1);

namespace Velm\Admin\Http\Controllers\Analytics;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Velm\Admin\Arch\PivotDataBuilder;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Views\ViewRegistry;

final class PivotDataController
{
    public function __invoke(
        Request $request,
        Environment $env,
        PivotDataBuilder $builder,
    ): JsonResponse {
        $model = (string) $request->query('model', '');

        if ($model === '' || ! $env->registry->has($model)) {
            return response()->json(['message' => 'Unknown model '.$model.'.'], 400);
        }

        $rowGroupby = (string) $request->query('row_groupby', '');
        $colGroupby = (string) $request->query('col_groupby', '');
        $measures = (string) $request->query('measures', '__count');
        $search = (string) $request->query('search', '');
        $module = (string) $request->query('module', '');
        $viewName = (string) $request->query('view', '');

        $rowSpecs = $this->splitSpecs($rowGroupby);
        $colSpecs = $this->splitSpecs($colGroupby);
        $measureSpecs = $this->splitSpecs($measures);

        try {
            $arch = $this->arch($env, $module, $viewName, $model);
            $payload = $builder->build(
                $arch,
                $env,
                $rowSpecs,
                $colSpecs,
                $measureSpecs !== [] ? $measureSpecs : ['__count'],
                $search,
            );
        } catch (AccessDeniedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }

        return response()->json($payload);
    }

    /**
     * @return list<string>
     */
    private function splitSpecs(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), explode(',', $value))));
    }

    /**
     * @return array<string, mixed>
     */
    private function arch(Environment $env, string $module, string $viewName, string $model): array
    {
        if ($module !== '' && $viewName !== '') {
            return app(ViewRegistry::class)->arch($env, $module, $viewName);
        }

        return [
            'view_type' => 'pivot',
            'model' => $model,
            'rows' => [],
            'cols' => [],
            'measures' => ['__count'],
        ];
    }
}
