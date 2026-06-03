<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Support\ResolvesStoredView;

final class CompanyListPage extends ArchListPage
{
    use ResolvesStoredView;

    protected function velmViewModule(): string
    {
        return 'base';
    }

    protected function velmViewName(): string
    {
        return 'company.list';
    }
}
