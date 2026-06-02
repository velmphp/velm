<?php

declare(strict_types=1);

namespace Velm\Views;

use Velm\Environment;
use Velm\Views\Arch\ArchNormalizer;

final class ViewRegistry
{
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

        if ($row['inherit_id'] !== null) {
            throw new \RuntimeException(
                "View {$module}.{$name} is an extension view; resolve inheritance is not implemented yet.",
            );
        }

        if ($row['arch'] === null || $row['arch'] === '') {
            throw new \RuntimeException("View {$module}.{$name} has no arch.");
        }

        /** @var array<string, mixed> $inner */
        $inner = json_decode((string) $row['arch'], true, flags: JSON_THROW_ON_ERROR);
        $normalized = ArchNormalizer::normalize($inner, (string) $row['view_type']);

        return [
            'view_type' => $row['view_type'],
            'model' => $row['model'],
            ...$normalized,
        ];
    }
}
