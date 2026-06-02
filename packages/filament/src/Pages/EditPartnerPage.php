<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\PartnerViews;

final class EditPartnerPage extends ArchEditPage
{
    protected static ?string $slug = 'partners/{record}/edit';

    /**
     * @return array<string, mixed>
     */
    protected static function arch(): array
    {
        return PartnerViews::form();
    }

    protected static function listPage(): string
    {
        return PartnerListPage::class;
    }
}
