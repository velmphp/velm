<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\ResolvesStoredView;

final class PartnerListPage extends ArchListPage
{
    use ResolvesStoredView;

    protected static ?string $navigationLabel = 'Partners';

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static function velmViewModule(): string
    {
        return 'partners';
    }

    protected static function velmViewName(): string
    {
        return 'partner.list';
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
