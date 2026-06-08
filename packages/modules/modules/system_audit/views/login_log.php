<?php

declare(strict_types=1);

use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionVariant;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('login_log.list')
            ->model('ir.login.log')
            ->title('Login history')
            ->readonly()
            ->detailView('login_log.detail')
            ->clickToOpen()
            ->pageActions([
                Action::make('Export CSV')
                    ->url('/web/audit/logins/export')
                    ->method('GET')
                    ->variant(ActionVariant::Secondary)
                    ->perm('read'),
            ])
            ->rowActions([ListRowAction::open()])
            ->columns([
                'created_at',
                'event',
                'user_id',
                'email',
                'ip_address',
                'session_lifetime_minutes',
            ]),
        DetailView::make('login_log.detail')
            ->model('ir.login.log')
            ->title('Login event')
            ->section('event', 'Event', [
                'created_at',
                'event',
                'user_id',
                'email',
                'ip_address',
                'user_agent',
                'session_id',
                'session_lifetime_minutes',
            ]),
    );
