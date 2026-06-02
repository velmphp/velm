<?php

declare(strict_types=1);

namespace Velm\Views;

final class ViewNotFoundException extends \RuntimeException
{
    public static function forView(string $module, string $name): self
    {
        return new self("View {$module}.{$name} was not found.");
    }
}
