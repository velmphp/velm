<?php

declare(strict_types=1);

namespace Velm\Views\Authoring;

use Velm\Views\Authoring\Concerns\DefinesSections;

/**
 * Inline form schema for arch-declared view actions (Filament-style action forms).
 */
final class ActionForm
{
    use DefinesSections;

    private ?string $model = null;

    public static function make(): self
    {
        return new self();
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return array{sections: list<array<string, mixed>>, cols?: int, model?: string}
     */
    public function toArray(): array
    {
        $arch = $this->sectionsArch();

        if ($this->model !== null && $this->model !== '') {
            $arch['model'] = $this->model;
        }

        return $arch;
    }
}
