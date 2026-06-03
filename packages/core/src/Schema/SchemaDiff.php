<?php

declare(strict_types=1);

namespace Velm\Schema;

use Velm\Fields\Field;
use Velm\Fields\Many2manyField;

final class SchemaDiff
{
    /** @var list<array{0: string, 1: class-string}> */
    public array $newTables = [];

    /** @var list<array{0: string, 1: string, 2: Field}> */
    public array $newColumns = [];

    /** @var list<array{0: string, 1: string}> */
    public array $orphanColumns = [];

    /** @var list<SchemaAlteration> */
    public array $alterations = [];

    public function isEmpty(): bool
    {
        return $this->newTables === []
            && $this->newColumns === []
            && $this->orphanColumns === []
            && $this->alterations === [];
    }

    /**
     * Whether {@see ModuleInstaller::sync()} can apply this diff (new tables/columns
     * and supported nullability alters). Orphan columns and unsupported alters are drift only.
     */
    public function isSyncActionable(bool $canAlterColumnNullability): bool
    {
        if ($this->newTables !== [] || $this->newColumns !== []) {
            return true;
        }

        return $canAlterColumnNullability && $this->alterations !== [];
    }

    public function hasDrift(): bool
    {
        return ! $this->isEmpty();
    }
}
