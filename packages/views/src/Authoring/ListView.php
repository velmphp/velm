<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class ListView implements ViewDeclaration
{
    /** @var list<mixed> */
    private array $fields = [];

    private ?string $model = null;

    private ?string $title = null;

    private ?string $formView = null;

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

    /**
     * @param  list<mixed>  $fields
     */
    public function columns(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function formView(string $formView): self
    {
        $this->formView = $formView;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->model === null) {
            throw new \LogicException("List view {$this->name} is missing model().");
        }

        $arch = [
            'fields' => array_map(
                static fn (mixed $field): array => $field instanceof ViewDeclaration ? $field->toArray() : (is_array($field) ? $field : ['name' => (string) $field]),
                $this->fields,
            ),
        ];

        if ($this->title !== null) {
            $arch['title'] = $this->title;
        }

        if ($this->formView !== null) {
            $arch['form_view'] = $this->formView;
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'list',
            'arch' => $arch,
        ];
    }
}
