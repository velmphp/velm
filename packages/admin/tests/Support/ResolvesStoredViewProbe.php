<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Velm\Admin\Pages\ArchListPage;
use Velm\Admin\Support\ResolvesStoredView;

final class ResolvesStoredViewProbe extends ArchListPage
{
    use ResolvesStoredView;

    /** @var array<string, mixed> */
    public array $probeArch = [
        'model' => 'res.partner',
        'fields' => [['name' => 'name']],
    ];

    protected function arch(): array
    {
        return $this->probeArch;
    }

    protected function velmViewModule(): string
    {
        return 'partners';
    }

    protected function velmViewName(): string
    {
        return 'partner.list';
    }

    public function exposeListFormViewName(): ?string
    {
        return $this->listFormViewName();
    }

    public function exposeListDetailViewName(): ?string
    {
        return $this->listDetailViewName();
    }

    public function exposeListEditViewName(): ?string
    {
        return $this->listEditViewName();
    }

    public function exposeCreatePageUrl(): ?string
    {
        return $this->createPageUrl();
    }

    public function exposeOpenRecordUrl(int $recordId): ?string
    {
        return $this->openRecordUrl($recordId);
    }

    public function exposeEditRecordUrl(int $recordId): ?string
    {
        return $this->editRecordUrl($recordId);
    }

    public function exposeSupportsRecordOpen(): bool
    {
        return $this->supportsRecordOpen();
    }

    public function exposeSupportsRecordEdit(): bool
    {
        return $this->supportsRecordEdit();
    }

    public function exposeListHasEditTarget(): bool
    {
        return $this->listHasEditTarget();
    }
}
