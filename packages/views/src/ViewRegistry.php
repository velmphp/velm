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
            throw new \RuntimeException("View {$module}.{$name} was not found.");
        }

        $row = $view->read()[0];
        $inner = $this->resolver->resolveRecord($env, $row);

        return [
            'view_type' => $row['view_type'],
            'model' => $row['model'],
            ...$inner,
        ];
    }
}
