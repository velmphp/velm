<?php

declare(strict_types=1);

namespace Velm;

use Velm\Database\Connection;
use Velm\Models\Model;
use Velm\Recordset\Recordset;

final class Environment
{
    public function __construct(
        public readonly Connection $connection,
        public readonly Registry $registry,
        public readonly int $uid = 1,
        /** @var array<string, mixed> */
        public readonly array $context = [],
        public readonly RecordCache $cache = new RecordCache,
    ) {}

    public function model(string $name): Recordset
    {
        $class = $this->registry->modelClass($name);

        return new Recordset($this, $class, []);
    }

    /**
     * @param  list<int>  $ids
     */
    public function browse(string $name, array $ids): Recordset
    {
        $class = $this->registry->modelClass($name);

        return new Recordset($this, $class, array_values($ids));
    }
}
