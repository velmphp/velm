<?php

declare(strict_types=1);

namespace Velm\Filament\Support;

use Velm\Environment;
use Velm\Views\ViewRegistry;

trait ResolvesStoredView
{
    abstract protected static function velmViewModule(): string;

    abstract protected static function velmViewName(): string;

    /**
     * @return array<string, mixed>
     */
    protected static function arch(): array
    {
        return app(ViewRegistry::class)->arch(
            app(Environment::class),
            static::velmViewModule(),
            static::velmViewName(),
        );
    }
}
