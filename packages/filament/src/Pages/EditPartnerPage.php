<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\ResolvesStoredView;

final class EditPartnerPage extends ArchEditPage
{
    use ResolvesStoredView;

    protected static ?string $slug = 'partners/{record}/edit';

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
