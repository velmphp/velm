<?php

declare(strict_types=1);

use Addons\ChangeManagement\Dashboard\ChangesSummaryWidget;
use Velm\Modules\Dashboard\DashboardData;

return DashboardData::make('change_management')
    ->widget(
        id: 'change_requests_summary',
        title: 'Change requests',
        model: 'it.change',
        view: 'velm-ui::dashboard.list-card',
        resolver: ChangesSummaryWidget::class.'::resolve',
        sequence: 30,
        size: 'half',
        icon: 'heroicon-o-wrench-screwdriver',
    );
