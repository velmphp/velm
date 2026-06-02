<?php

declare(strict_types=1);

namespace Velm;

final class RecordCache
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function get(string $model, int $id, string $field): mixed
    {
        return $this->data[$this->key($model, $id, $field)] ?? null;
    }

    public function set(string $model, int $id, string $field, mixed $value): void
    {
        $this->data[$this->key($model, $id, $field)] = $value;
    }

    public function has(string $model, int $id, string $field): bool
    {
        return array_key_exists($this->key($model, $id, $field), $this->data);
    }

    public function forget(string $model, int $id, ?string $field = null): void
    {
        if ($field !== null) {
            unset($this->data[$this->key($model, $id, $field)]);

            return;
        }

        $prefix = $model."\0".$id."\0";

        foreach (array_keys($this->data) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->data[$key]);
            }
        }
    }

    public function clear(): void
    {
        $this->data = [];
    }

    private function key(string $model, int $id, string $field): string
    {
        return $model."\0".$id."\0".$field;
    }
}
