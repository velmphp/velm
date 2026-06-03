<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Concerns\DefinesSections;
use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class DetailView implements ViewDeclaration
{
    use DefinesSections;

    private ?string $model = null;

    private ?string $title = null;

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

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->model === null) {
            throw new \LogicException("Detail view {$this->name} is missing model().");
        }

        $arch = $this->sectionsArch();

        if ($this->title !== null) {
            $arch['title'] = $this->title;
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'detail',
            'arch' => $arch,
        ];
    }
}
