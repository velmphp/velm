<?php

declare(strict_types=1);

namespace Velm\Schema;

final readonly class SchemaAlteration
{
    public function __construct(
        public string $table,
        public string $column,
        public string $kind,
        public string $detail,
    ) {}

    public function cliLine(): string
    {
        return "  ~ {$this->table}.{$this->column}: {$this->kind} — {$this->detail}";
    }
}
