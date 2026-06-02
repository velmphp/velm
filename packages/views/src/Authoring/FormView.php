<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class FormView implements ViewDeclaration
{
    /** @var list<array<string, mixed>> */
    private array $sections = [];

    private ?string $model = null;

    private function __construct(
        private readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function section(string $name, string $title, array $fields): self
    {
        $this->sections[] = [
            'name' => $name,
            'title' => $title,
            'fields' => array_map(
                static fn (mixed $field): array => $field instanceof ViewDeclaration ? $field->toArray() : (is_array($field) ? $field : ['name' => (string) $field]),
                $fields,
            ),
        ];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->model === null) {
            throw new \LogicException("Form view {$this->name} is missing model().");
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'form',
            'arch' => [
                'sections' => $this->sections,
            ],
        ];
    }
}
