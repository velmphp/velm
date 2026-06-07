<?php

declare(strict_types=1);

namespace Velm\Admin\Http\Controllers\Analytics;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Velm\Admin\Arch\GraphDataBuilder;
use Velm\Admin\Arch\ViewFieldCatalog;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Views\ViewRegistry;

final class GraphDataController
{
    public function __invoke(
        Request $request,
        Environment $env,
        GraphDataBuilder $builder,
    ): JsonResponse {
        $model = (string) $request->query('model', '');

        if ($model === '' || ! $env->registry->has($model)) {
            return response()->json(['message' => 'Unknown model '.$model.'.'], 400);
        }

        $groupBy = (string) $request->query('groupby', $request->query('group_by', ''));
        $measure = (string) $request->query('measure', '__count');
        $search = (string) $request->query('search', '');
        $module = (string) $request->query('module', '');
        $viewName = (string) $request->query('view', '');

        try {
            $arch = $this->arch($env, $module, $viewName, $model, 'graph');
            $payload = $builder->build($arch, $env, $groupBy, $measure, $search);
        } catch (AccessDeniedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }

        return response()->json([
            'labels' => $payload['labels'],
            'values' => $payload['values'],
            'measure_label' => $payload['measure_label'],
            'chart_type' => $request->query('chart', $payload['chart']),
            'groupby' => $payload['group_by'],
            'measure' => $payload['measure'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function arch(Environment $env, string $module, string $viewName, string $model, string $type): array
    {
        if ($module !== '' && $viewName !== '') {
            return app(ViewRegistry::class)->arch($env, $module, $viewName);
        }

        return [
            'view_type' => $type,
            'model' => $model,
            'group_by' => '',
            'measures' => ['__count'],
            'chart' => 'bar',
        ];
    }
}
