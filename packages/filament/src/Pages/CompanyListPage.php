<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\ResolvesStoredView;

final class CompanyListPage extends ArchListPage
{
    use ResolvesStoredView;

    protected static function velmViewModule(): string
    {
        return 'base';
    }

    protected static function velmViewName(): string
    {
        return 'company.list';
    }

    protected static function createPage(): ?string
    {
        return CreateCompanyPage::class;
    }

    protected static function editPage(): ?string
    {
        return EditCompanyPage::class;
    }
}
