<?php

declare(strict_types=1);

namespace Velm\Views\Arch;

use Velm\Views\Arch\Contracts\SortsViewExtensions;

final class PriorityViewExtensionSorter implements SortsViewExtensions
{
    /**
     * @param  list<array<string, mixed>>  $extensions
     * @return list<array<string, mixed>>
     */
    public function sort(array $extensions): array
    {
        usort(
            $extensions,
            static fn (array $a, array $b): int => ((int) ($a['priority'] ?? 16)) <=> ((int) ($b['priority'] ?? 16))
                ?: ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)),
        );

        return $extensions;
    }
}
