<?php

declare(strict_types=1);

namespace Velm\Views\Authoring\Contracts;

interface MenuDeclaration
{
    /**
     * Expand nested groups into a flat list for {@see \Velm\Views\Sync\MenuSynchronizer}.
     *
     * @return list<array<string, mixed>>
     */
    public function flatten(): array;
}
