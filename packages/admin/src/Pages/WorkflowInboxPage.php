<?php

declare(strict_types=1);

namespace Velm\Admin\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Velm\Admin\Concerns\ReconcilesVelmModuleUi;
use Velm\Environment;
use Velm\Modules\Workflow\WorkflowInbox;

final class WorkflowInboxPage extends VelmShellPage
{
    use ReconcilesVelmModuleUi;

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(Environment $env): void
    {
        $this->reconcileVelmModuleUi('workflow');

        $this->items = WorkflowInbox::listItems($env);
    }

    public function getTitle(): string|Htmlable
    {
        return __('My approvals');
    }

    public function render()
    {
        return view('velm-ui::workflow.inbox', [
            'items' => $this->items,
        ]);
    }
}
