<?php

declare(strict_types=1);

namespace Velm\Views\Authoring\Concerns;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

trait DefinesSections
{
    private ?int $cols = null;

    /** @var list<array<string, mixed>> */
    private array $sections = [];

    public function cols(int $cols): static
    {
        if ($cols < 1) {
            throw new \InvalidArgumentException('cols must be at least 1.');
        }

        $this->cols = $cols;

        return $this;
    }

    public function section(string $name, string $title, array $fields, ?int $cols = null): static
    {
        $entry = [
            'name' => $name,
            'title' => $title,
            'fields' => self::normalizeFieldSpecs($fields),
        ];

        if ($cols !== null) {
            if ($cols < 1) {
                throw new \InvalidArgumentException('Section cols must be at least 1.');
            }

            $entry['cols'] = $cols;
        }

        $this->sections[] = $entry;

        return $this;
    }

    /**
     * @param  list<array{name: string, title?: string, fields: list<mixed>, cols?: int}>  $pages
     */
    public function notebook(string $name, string $title, array $pages, ?int $cols = null): static
    {
        $normalized = [];

        foreach ($pages as $page) {
            $normalized[] = [
                'name' => $page['name'],
                'title' => $page['title'] ?? $page['name'],
                'fields' => self::normalizeFieldSpecs($page['fields'] ?? []),
                'cols' => $page['cols'] ?? null,
            ];
        }

        $entry = [
            'name' => $name,
            'title' => $title,
            'pages' => $normalized,
        ];

        if ($cols !== null) {
            if ($cols < 1) {
                throw new \InvalidArgumentException('Notebook cols must be at least 1.');
            }

            $entry['cols'] = $cols;
        }

        $this->sections[] = $entry;

        return $this;
    }

    /**
     * @param  list<mixed>  $fields
     * @return list<array<string, mixed>>
     */
    private static function normalizeFieldSpecs(array $fields): array
    {
        return array_map(
            static fn (mixed $field): array => $field instanceof ViewDeclaration ? $field->toArray() : (is_array($field) ? $field : ['name' => (string) $field]),
            $fields,
        );
    }

    /**
     * @return array{sections: list<array<string, mixed>>, cols?: int}
     */
    protected function sectionsArch(): array
    {
        $arch = [
            'sections' => $this->sections,
        ];

        if ($this->cols !== null) {
            $arch['cols'] = $this->cols;
        }

        return $arch;
    }
}
