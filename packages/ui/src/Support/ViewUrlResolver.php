<?php

declare(strict_types=1);

namespace Velm\Ui\Support;

use Velm\Environment;
use Velm\Ui\Forms\FormMode;

final class ViewUrlResolver
{
    public static function viewHref(string $module, string $viewName): string
    {
        return "/velm/views/{$module}/{$viewName}";
    }

    public static function recordHref(string $formViewUrl, int $recordId): string
    {
        return rtrim($formViewUrl, '/').'/'.$recordId;
    }

    public static function recordEditHref(string $formViewUrl, int $recordId): string
    {
        return self::recordHref($formViewUrl, $recordId).'/edit';
    }

    public static function createHref(string $formViewUrl): string
    {
        return rtrim($formViewUrl, '/').'/create';
    }

    public static function formUrlForModel(Environment $env, string $comodel): ?string
    {
        return self::viewUrlForModel($env, $comodel, 'form');
    }

    public static function detailUrlForModel(Environment $env, string $comodel): ?string
    {
        return self::viewUrlForModel($env, $comodel, 'detail');
    }

    public static function recordViewUrlForModel(Environment $env, string $comodel, FormMode $mode): ?string
    {
        if ($mode === FormMode::Display) {
            return self::detailUrlForModel($env, $comodel)
                ?? self::formUrlForModel($env, $comodel);
        }

        return self::formUrlForModel($env, $comodel);
    }

    private static function viewUrlForModel(Environment $env, string $comodel, string $viewType): ?string
    {
        if (! $env->registry->has('ir.ui.view')) {
            return null;
        }

        $views = $env->model('ir.ui.view')->search([
            ['model', '=', $comodel],
            ['view_type', '=', $viewType],
        ]);

        if ($views->count() === 0) {
            return null;
        }

        $row = $views->read()[0];

        return self::viewHref((string) $row['module'], (string) $row['name']);
    }

    public static function listUrlForModel(Environment $env, string $comodel): ?string
    {
        if (! $env->registry->has('ir.ui.view')) {
            return null;
        }

        $views = $env->model('ir.ui.view')->search([
            ['model', '=', $comodel],
            ['view_type', '=', 'list'],
        ]);

        if ($views->count() === 0) {
            return null;
        }

        $row = $views->read()[0];

        return self::viewHref((string) $row['module'], (string) $row['name']);
    }
}
