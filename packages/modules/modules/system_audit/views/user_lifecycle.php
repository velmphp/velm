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
        ListView::make('user_lifecycle.list')
            ->model('ir.user.lifecycle')
            ->title('User lifecycle')
            ->readonly()
            ->detailView('user_lifecycle.detail')
            ->clickToOpen()
            ->pageActions([
                Action::make('Export CSV')
                    ->url('/web/audit/lifecycle/export')
                    ->method('GET')
                    ->variant(ActionVariant::Secondary)
                    ->perm('read'),
            ])
            ->rowActions([ListRowAction::open()])
            ->columns([
                'created_at',
                'event',
                'user_id',
                'actor_id',
            ]),
        DetailView::make('user_lifecycle.detail')
            ->model('ir.user.lifecycle')
            ->title('Lifecycle event')
            ->section('event', 'Event', [
                'created_at',
                'event',
                'user_id',
                'actor_id',
                'detail',
            ]),
    );
