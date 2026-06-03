<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Support\ResolvesStoredView;

final class PartnerListPage extends ArchListPage
{
    use ResolvesStoredView;

    protected function velmViewModule(): string
    {
        return 'partners';
    }

    protected function velmViewName(): string
    {
        return 'partner.list';
    }
}
