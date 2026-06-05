<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

/**
 * Section arch node for inherit after/before operations.
 */
final class Section implements ViewDeclaration
{
    private ?int $cols = null;

    /** @var list<array<string, mixed>> */
    private array $fields = [];

    private function __construct(
        private readonly string $name,
        private readonly string $title,
    ) {}

    public static function make(string $name, string $title): self
    {
        return new self($name, $title);
    }

    public function cols(int $cols): self
    {
        if ($cols < 1) {
            throw new \InvalidArgumentException('Section cols must be at least 1.');
        }

        $this->cols = $cols;

        return $this;
    }

    public function fields(string|Field|array ...$fields): self
    {
        $this->fields = self::normalizeFieldSpecs($fields);

        return $this;
    }

    /**
     * @param  list<mixed>  $fields
     * @return list<array<string, mixed>>
     */
    private static function normalizeFieldSpecs(array $fields): array
    {
        return array_map(
            static fn (mixed $field): array => $field instanceof ViewDeclaration
                ? $field->toArray()
                : (is_array($field) ? $field : ['name' => (string) $field]),
            $fields,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $section = [
            'name' => $this->name,
            'title' => $this->title,
            'fields' => $this->fields,
        ];

        if ($this->cols !== null) {
            $section['cols'] = $this->cols;
        }

        return $section;
    }
}
