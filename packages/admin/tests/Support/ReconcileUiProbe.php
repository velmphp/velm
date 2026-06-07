<?php

declare(strict_types=1);

namespace Velm\Admin\Tests\Support;

use Livewire\Component;
use Velm\Admin\Concerns\ReconcilesVelmModuleUi;

final class ReconcileUiProbe extends Component
{
    use ReconcilesVelmModuleUi;

    public function run(?string $module = null): void
    {
        $this->reconcileVelmModuleUi($module);
    }

    public function velmViewModule(): string
    {
        return 'partners';
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
