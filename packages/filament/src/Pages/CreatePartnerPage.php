<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\ResolvesStoredView;

final class CreatePartnerPage extends ArchCreatePage
{
    use ResolvesStoredView;

    protected static ?string $slug = 'partners/create';

    protected static function velmViewModule(): string
    {
        return 'partners';
    }

    protected static function velmViewName(): string
    {
        return 'partner.form';
    }

    protected static function listPage(): string
    {
        return PartnerListPage::class;
    }
}
