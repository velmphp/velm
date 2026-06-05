<?php

declare(strict_types=1);

namespace Velm\Modules;

use Velm\Views\Arch\Contracts\SortsViewExtensions;

/**
 * Apply view inherits in installed-module dependency order (manifest DEPENDS).
 *
 * Third-party modules do not need to coordinate on inherit priority: a module
 * that depends on another always has its patches applied later.
 */
final class ModuleDependencyViewExtensionSorter implements SortsViewExtensions
{
    public function __construct(
        private readonly ModuleDiscovery $discovery = new ModuleDiscovery,
        private readonly DependencyResolver $resolver = new DependencyResolver,
        private readonly ModuleRepository $repository = new ModuleRepository,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $extensions
     * @return list<array<string, mixed>>
     */
    public function sort(array $extensions): array
    {
        if ($extensions === []) {
            return [];
        }

        $ranks = $this->moduleRanks();

        usort(
            $extensions,
            static fn (array $a, array $b): int => ($ranks[(string) ($a['module'] ?? '')] ?? PHP_INT_MAX)
                <=> ($ranks[(string) ($b['module'] ?? '')] ?? PHP_INT_MAX)
                ?: ((int) ($a['priority'] ?? 16)) <=> ((int) ($b['priority'] ?? 16))
                ?: ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)),
        );

        return $extensions;
    }

    /**
     * @return array<string, int>
     */
    private function moduleRanks(): array
    {
        /** @var list<string> $roots */
        $roots = function_exists('config')
            ? (array) config('velm.addon_paths', [])
            : [];

        if ($roots === []) {
            return [];
        }

        $specs = $this->discovery->discover($roots);
        $ranks = [];
        $index = 0;

        foreach ($this->resolver->resolve($specs) as $spec) {
            if (! $this->repository->isInstalled($spec->name)) {
                continue;
            }

            $ranks[$spec->name] = $index;
            $index++;
        }

        return $ranks;
    }
}
