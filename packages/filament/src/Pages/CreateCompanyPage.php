<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\ResolvesStoredView;

final class CreateCompanyPage extends ArchCreatePage
{
    use ResolvesStoredView;

    protected static ?string $slug = 'companies/create';

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
