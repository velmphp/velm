<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class InheritView implements ViewDeclaration
{
    /**
     * @param  list<array<string, mixed>>  $operations
     */
    private function __construct(
        private readonly string $name,
        private readonly string $inherit,
        private readonly array $operations,
        private readonly int $priority,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $operations
     */
    public static function make(
        string $name,
        string $inherit,
        array $operations,
        int $priority = 20,
    ): self {
        return new self($name, $inherit, $operations, $priority);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'inherit' => $this->inherit,
            'operations' => $this->operations,
            'priority' => $this->priority,
        ];
    }
}
