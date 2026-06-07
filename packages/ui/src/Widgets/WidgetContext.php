<?php

declare(strict_types=1);

namespace Velm\Ui\Widgets;

use Velm\Environment;
use Velm\Fields\Field;
use Velm\Ui\Forms\FormMode;

final readonly class WidgetContext
{
    /**
     * @param  array<string, mixed>  $spec  Normalized arch field spec
     * @param  array<string, mixed>  $data  Livewire form state (`data.*`)
     */
    public function __construct(
        public Environment $env,
        public string $model,
        public array $spec,
        public FormMode $mode,
        public array $data,
        public ?string $error = null,
    ) {}

    public function fieldName(): string
    {
        return (string) $this->spec['name'];
    }

    public function value(mixed $default = null): mixed
    {
        return $this->data[$this->fieldName()] ?? $default;
    }

    public function velmField(): ?Field
    {
        if ($this->model === '') {
            return null;
        }

        return $this->env->registry->field($this->model, $this->fieldName());
    }

    public function wireKey(): string
    {
        return 'data.'.$this->fieldName();
    }
}
