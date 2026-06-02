<?php

declare(strict_types=1);

namespace Velm\Views\Authoring\Contracts;

interface MenuDeclaration
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
