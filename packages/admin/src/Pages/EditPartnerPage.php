<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Velm\Admin\Support\ResolvesStoredView;
use Velm\Admin\Support\StoredViewRoutes;

final class EditPartnerPage extends ArchEditPage
{
    use ResolvesStoredView;

    protected static ?string $slug = 'partners/{record}/edit';

    protected function velmViewModule(): string
    {
        return 'partners';
    }

    protected function velmViewName(): string
    {
        return 'partner.form';
    }

    protected function listPageUrl(): string
    {
        return StoredViewRoutes::listPageUrl('partners', 'partner.list');
    }
}
