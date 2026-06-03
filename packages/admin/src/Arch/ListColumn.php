<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

final readonly class ListColumn
{
    public function __construct(
        public string $name,
        public string $kind,
        public ?string $comodel = null,
    ) {}
}
