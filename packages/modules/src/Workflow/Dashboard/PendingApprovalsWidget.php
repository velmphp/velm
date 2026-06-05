<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow\Dashboard;

use Velm\Environment;
use Velm\Modules\Workflow\WorkflowInbox;

final class PendingApprovalsWidget
{
    /**
     * @return array<string, mixed>
     */
    public static function resolve(Environment $env): array
    {
        $items = array_slice(WorkflowInbox::listItems($env), 0, 5);

        return [
            'items' => $items,
            'empty_label' => 'No approvals waiting for you.',
            'href' => '/web/workflow/inbox',
            'action_label' => 'Open inbox',
        ];
    }
}
