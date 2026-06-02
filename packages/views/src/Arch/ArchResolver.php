<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

use Velm\Environment;

final class ArchResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(Environment $env, string $module, string $name): array
    {
        $view = $env->model('ir.ui.view')->search([
            ['module', '=', $module],
            ['name', '=', $name],
        ]);

        if ($view->count() === 0) {
            throw new \RuntimeException("View {$module}.{$name} was not found.");
        }

        return $this->resolveRecord($env, $view->read()[0]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function resolveRecord(Environment $env, array $row): array
    {
        $root = $this->walkToRoot($env, $row);

        if ($root['arch'] === null || $root['arch'] === '') {
            throw new \RuntimeException(
                "View {$root['module']}.{$root['name']} has no arch.",
            );
        }

        /** @var array<string, mixed> $arch */
        $arch = json_decode((string) $root['arch'], true, flags: JSON_THROW_ON_ERROR);
        $arch = json_decode(json_encode($arch, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->applyExtensionChain($env, $root, $arch);

        $viewType = (string) $root['view_type'];

        return ArchNormalizer::normalize($arch, $viewType);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function walkToRoot(Environment $env, array $row): array
    {
        while ($row['inherit_id'] !== null) {
            $parent = $env->browse('ir.ui.view', [(int) $row['inherit_id']]);

            if ($parent->count() === 0) {
                throw new \RuntimeException('Parent view '.$row['inherit_id'].' was not found.');
            }

            $row = $parent->read()[0];
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $viewRow
     * @param  array<string, mixed>  $arch
     */
    private function applyExtensionChain(Environment $env, array $viewRow, array &$arch): void
    {
        $extensions = $env->model('ir.ui.view')->search(
            [['inherit_id', '=', (int) $viewRow['id']]],
            order: '"priority" ASC, "id" ASC',
        );

        foreach ($extensions->ids() as $extensionId) {
            $extension = $env->browse('ir.ui.view', [$extensionId])->read()[0];

            if ($extension['operations'] !== null && $extension['operations'] !== '') {
                /** @var list<array<string, mixed>> $ops */
                $ops = json_decode((string) $extension['operations'], true, flags: JSON_THROW_ON_ERROR);
                ArchOperations::apply($arch, $ops);
            }

            $this->applyExtensionChain($env, $extension, $arch);
        }
    }
}
