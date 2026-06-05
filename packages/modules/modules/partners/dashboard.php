<?php

declare(strict_types=1);

use Velm\Modules\Dashboard\DashboardData;
use Velm\Modules\Partners\Dashboard\PartnersSummaryWidget;

return DashboardData::make('partners')
    ->widget(
        id: 'partners_summary',
        title: 'Contacts',
        model: 'res.partner',
        view: 'velm-ui::dashboard.stat-card',
        resolver: PartnersSummaryWidget::class.'::resolve',
        sequence: 20,
        size: 'third',
        icon: 'heroicon-o-user-group',
    );
