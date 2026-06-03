<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Support\ResolvesStoredView;

final class StoredViewListPage extends ArchListPage
{
    use ResolvesStoredView;

    protected static ?string $slug = 'views/{module}/{viewName}';

    public string $module = '';

    public string $viewName = '';

    protected function velmViewModule(): string
    {
        return $this->module;
    }

    protected function velmViewName(): string
    {
        return $this->viewName;
    }
}
