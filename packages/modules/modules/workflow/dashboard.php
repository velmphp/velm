<?php

declare(strict_types=1);

use Velm\Modules\Dashboard\DashboardData;
use Velm\Modules\Workflow\Dashboard\PendingApprovalsWidget;

return DashboardData::make('workflow')
    ->widget(
        id: 'workflow_pending_approvals',
        title: 'Pending approvals',
        model: 'workflow.approval',
        view: 'velm-ui::dashboard.list-card',
        resolver: PendingApprovalsWidget::class.'::resolve',
        sequence: 10,
        size: 'half',
        icon: 'heroicon-o-clipboard-document-check',
    );
