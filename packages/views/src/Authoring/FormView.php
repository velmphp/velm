<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Concerns\DefinesSections;
use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class FormView implements ViewDeclaration
{
    use DefinesSections;

    private ?string $model = null;

    /** @var list<array<string, mixed>> */
    private array $headerActions = [];

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
     * @param  list<Action|array<string, mixed>>  $actions
     */
    public function headerActions(array $actions): self
    {
        $this->headerActions = array_map(
            static fn (Action|array $action): array => $action instanceof Action
                ? $action->toArray()
                : $action,
            $actions,
        );

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

        $arch = $this->sectionsArch();

        if ($this->headerActions !== []) {
            $arch['header_actions'] = $this->headerActions;
        }

        return [
            'name' => $this->name,
            'model' => $this->model,
            'view_type' => 'form',
            'arch' => $arch,
        ];
    }
}
