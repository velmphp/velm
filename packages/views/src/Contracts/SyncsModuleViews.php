<?php

declare(strict_types=1);

namespace Velm\Views\Contracts;

interface SyncsModuleViews
{
    public function isInstalled(string $module): bool;

    public function sync(string $module): void;
}
