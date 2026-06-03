<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Concerns\DefinesSections;
use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class FormView implements ViewDeclaration
{
    use DefinesSections;

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
            'arch' => $this->sectionsArch(),
        ];
    }
}
