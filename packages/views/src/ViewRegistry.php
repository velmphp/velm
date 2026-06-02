<?php

declare(strict_types=1);

namespace Velm\Views;

use Velm\Environment;
use Velm\Views\Arch\ArchResolver;

final class ViewRegistry
{
    public function __construct(
        private readonly ArchResolver $resolver = new ArchResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function arch(Environment $env, string $module, string $name): array
    {
        $view = $env->model('ir.ui.view')->search([
            ['module', '=', $module],
            ['name', '=', $name],
        ]);

        if ($view->count() === 0) {
            throw ViewNotFoundException::forView($module, $name);
        }

        $row = $view->read()[0];
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
        $view = $env->model('ir.ui.view')->search([
            ['module', '=', $module],
            ['name', '=', $name],
        ]);

        if ($view->count() === 0) {
            throw ViewNotFoundException::forView($module, $name);
        }

        $row = $view->read()[0];

        return [
            'id' => (int) $row['id'],
            'module' => (string) $row['module'],
            'name' => (string) $row['name'],
            'model' => (string) $row['model'],
            'view_type' => (string) $row['view_type'],
            'arch' => $this->resolver->resolveRecord($env, $row),
        ];
    }
}
