<?php

declare(strict_types=1);

namespace Velm\Schema;

final class SchemaApplyResult
{
    public int $setNotNull = 0;

    public int $skippedNotNull = 0;

    /** @var list<string> */
    public array $skippedNotNullColumns = [];

    public function __construct(
        public readonly SchemaDiff $diff,
    ) {}
}
