<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\PartnerViews;

final class PartnerListPage extends ArchListPage
{
    protected static ?string $navigationLabel = 'Partners';

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    /**
     * @return array<string, mixed>
     */
    protected static function arch(): array
    {
        return PartnerViews::list();
    }

    protected static function createPage(): ?string
    {
        return CreatePartnerPage::class;
    }

    protected static function editPage(): ?string
    {
        return EditPartnerPage::class;
    }
}
