<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\ResolvesStoredView;

final class EditCompanyPage extends ArchEditPage
{
    use ResolvesStoredView;

    protected static ?string $slug = 'companies/{record}/edit';

    protected static function velmViewModule(): string
    {
        return 'base';
    }

    protected static function velmViewName(): string
    {
        return 'company.form';
    }

    protected static function listPage(): string
    {
        return CompanyListPage::class;
    }
}
