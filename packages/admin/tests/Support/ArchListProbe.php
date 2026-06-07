<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Velm\Admin\Pages\ArchListPage;
use Velm\Admin\Support\ResolvesStoredView;

final class ArchListProbe extends ArchListPage
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

    protected function openRecordUrl(int $recordId): ?string
    {
        return null;
    }

    protected function editRecordUrl(int $recordId): ?string
    {
        return null;
    }

    protected function velmViewModule(): string
    {
        return 'partners';
    }

    protected function velmViewName(): string
    {
        return 'partner.list';
    }
}
