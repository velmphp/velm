<?php

declare(strict_types=1);

namespace Velm\Views;

use Illuminate\Contracts\Foundation\Application;
use Velm\Environment;
use Velm\Views\Arch\ArchResolver;
use Velm\Views\Contracts\SyncsModuleViews;

final class ViewRegistry
{
    /** @var array<string, true> */
    private static array $syncAttempted = [];

    public function __construct(
        private readonly ArchResolver $resolver = new ArchResolver,
        private readonly ?Application $app = null,
    ) {}

    public function has(Environment $env, string $module, string $name): bool
    {
        return $this->findView($env, $module, $name)->count() > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function arch(Environment $env, string $module, string $name): array
    {
        $row = $this->requireViewRow($env, $module, $name);
        $inner = $this->resolver->resolveRecord($env, $row);

        return [
            'view_type' => $row['view_type'],
            'model' => $row['model'],
            ...$inner,
        ];
    }

    /**
     * JSON API payload (PyVelm {@code GET /api/views/{module}/{name}} parity).
     *
     * @return array{
     *     id: int,
     *     module: string,
     *     name: string,
     *     model: string,
     *     view_type: string,
     *     arch: array<string, mixed>
     * }
     */
    public function apiPayload(Environment $env, string $module, string $name): array
    {
        $row = $this->requireViewRow($env, $module, $name);

        return [
            'id' => (int) $row['id'],
            'module' => (string) $row['module'],
            'name' => (string) $row['name'],
            'model' => (string) $row['model'],
            'view_type' => (string) $row['view_type'],
            'arch' => $this->resolver->resolveRecord($env, $row),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requireViewRow(Environment $env, string $module, string $name): array
    {
        $view = $this->findView($env, $module, $name);

        if ($view->count() === 0 && $this->syncModuleViews($module)) {
            $view = $this->findView($env, $module, $name);
        }

        if ($view->count() === 0) {
            throw ViewNotFoundException::forView($module, $name);
        }

        return $view->read()[0];
    }

    private function findView(Environment $env, string $module, string $name): \Velm\Recordset\Recordset
    {
        return $env->model('ir.ui.view')->search([
            ['module', '=', $module],
            ['name', '=', $name],
        ]);
    }

    private function syncModuleViews(string $module): bool
    {
        if (isset(self::$syncAttempted[$module])) {
            return false;
        }

        self::$syncAttempted[$module] = true;

        $app = $this->app ?? (function_exists('app') ? app() : null);

        if ($app === null || ! $app->bound(SyncsModuleViews::class)) {
            return false;
        }

        $sync = $app->make(SyncsModuleViews::class);

        if (! $sync->isInstalled($module)) {
            return false;
        }

        $sync->sync($module);

        return true;
    }
}
