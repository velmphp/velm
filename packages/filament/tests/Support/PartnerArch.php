<?php

declare(strict_types=1);

namespace Velm\Filament\Tests\Support;

use Velm\Environment;
use Velm\Views\ViewRegistry;

final class PartnerArch
{
    /**
     * @return array<string, mixed>
     */
    public static function list(Environment $env): array
    {
        return (new ViewRegistry)->arch($env, 'partners', 'partner.list');
    }

    /**
     * @return array<string, mixed>
     */
    public static function form(Environment $env): array
    {
        return (new ViewRegistry)->arch($env, 'partners', 'partner.form');
    }
}
