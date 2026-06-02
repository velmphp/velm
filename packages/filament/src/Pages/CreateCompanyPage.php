<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\CompanyViews;

final class CreateCompanyPage extends ArchCreatePage
{
    protected static ?string $slug = 'companies/create';

    /**
     * @return array<string, mixed>
     */
    protected static function arch(): array
    {
        return CompanyViews::form();
    }

    protected static function listPage(): string
    {
        return CompanyListPage::class;
    }
}
