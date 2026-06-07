<?php

declare(strict_types=1);

namespace Velm\Ui\Tests\Livewire;

use Velm\Ui\Forms\FormMode;

final class EmbedCloseArchFormProbe extends ArchFormProbe
{
    public bool $embedded = true;

    public bool $skipRedirect = false;

    public FormMode $mode = FormMode::New;

    protected function velmFormEmbedRecordUrl(int $recordId): ?string
    {
        return null;
    }
}
