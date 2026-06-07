<?php

declare(strict_types=1);

namespace Velm\Admin\Http\Controllers\Analytics;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Velm\Admin\Arch\ViewFieldCatalog;
use Velm\Environment;

final class ViewFieldsController
{
    public function __invoke(
        Request $request,
        Environment $env,
        ViewFieldCatalog $catalog,
    ): JsonResponse {
        $model = (string) $request->query('model', '');

        if ($model === '' || ! $env->registry->has($model)) {
            return response()->json(['message' => 'Unknown model '.$model.'.'], 400);
        }

        try {
            return response()->json($catalog->forModel($model, $env));
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }
    }
}
