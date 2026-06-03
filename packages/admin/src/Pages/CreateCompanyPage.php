<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Concerns\StoredViewDetailRedirect;
use Velm\Admin\Support\ResolvesStoredView;
use Velm\Admin\Support\StoredViewRoutes;

final class CreateCompanyPage extends ArchCreatePage
{
    use ResolvesStoredView;
    use StoredViewDetailRedirect;

    protected static ?string $slug = 'companies/create';

    protected function velmViewModule(): string
    {
        return 'base';
    }

    protected function velmViewName(): string
    {
        return 'company.form';
    }

    protected function listPageUrl(): string
    {
        return StoredViewRoutes::listPageUrl('base', 'company.list');
    }
}
