<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Velm\Admin\Concerns\InteractsWithVelmListPresentation;

final class ListPresentationProbe
{
    use InteractsWithVelmListPresentation;

    /** @param array<string, mixed> $arch */
    public function __construct(
        private array $arch = [],
        private bool $openTarget = false,
        private bool $editTarget = false,
    ) {}

    protected function arch(): array
    {
        return $this->arch;
    }

    protected function openRecordUrl(int $recordId): ?string
    {
        return $this->openTarget ? "/open/{$recordId}" : null;
    }

    protected function editRecordUrl(int $recordId): ?string
    {
        return $this->editTarget ? "/edit/{$recordId}" : null;
    }

    protected function listHasOpenTarget(): bool
    {
        return $this->openTarget;
    }

    protected function listHasEditTarget(): bool
    {
        return $this->editTarget;
    }
}
