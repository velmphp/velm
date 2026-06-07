<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Velm\Admin\Pages\StoredViewPage;

final class StoredViewPageProbe extends StoredViewPage
{
    protected function reconcileVelmModuleUi(?string $module = null): void
    {
        // Keep patched ir.ui.view rows intact during mount coverage tests.
    }
}
