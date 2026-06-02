<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\CompanyViews;

final class CompanyListPage extends ArchListPage
{
    protected static ?string $navigationLabel = 'Companies';

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    /**
     * @return array<string, mixed>
     */
    protected static function arch(): array
    {
        return CompanyViews::list();
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
