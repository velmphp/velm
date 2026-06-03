<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

final readonly class ListQuery
{
    /**
     * @param  list<array{field: ?string, op: string, value: mixed, label: string}>  $filterChips
     */
    public function __construct(
        public string $search = '',
        public array $filterChips = [],
        public ?string $groupBy = null,
    ) {}
}
