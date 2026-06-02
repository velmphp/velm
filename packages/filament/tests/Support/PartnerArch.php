<?php

declare(strict_types=1);

namespace Velm\Filament\Tests\Support;

use Velm\Filament\Support\PartnerViews;

/** @deprecated Use {@see PartnerViews} directly. */
final class PartnerArch
{
    /**
     * @return array<string, mixed>
     */
    public static function list(): array
    {
        return PartnerViews::list();
    }

    /**
     * @return array<string, mixed>
     */
    public static function form(): array
    {
        return PartnerViews::form();
    }
}
