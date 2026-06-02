<?php

declare(strict_types=1);

namespace Velm\Filament\Pages;

use Velm\Filament\Support\PartnerViews;

final class CreatePartnerPage extends ArchCreatePage
{
    protected static ?string $slug = 'partners/create';

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
