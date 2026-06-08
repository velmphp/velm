<?php

declare(strict_types=1);

use Velm\Views\Authoring\Action;
use Velm\Views\Authoring\ActionVariant;
use Velm\Views\Authoring\DetailView;
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('audit_log.list')
            ->model('ir.audit.log')
            ->title('Audit log')
            ->readonly()
            ->detailView('audit_log.detail')
            ->clickToOpen()
            ->pageActions([
                Action::make('Export CSV')
                    ->url('/web/audit/logs/export')
                    ->method('GET')
                    ->variant(ActionVariant::Secondary)
                    ->perm('read'),
            ])
            ->rowActions([ListRowAction::open()])
            ->columns([
                'created_at',
                'name',
                'model',
                'res_id',
                'action',
                'user_id',
                'ip_address',
            ]),
        DetailView::make('audit_log.detail')
            ->model('ir.audit.log')
            ->title('Audit entry')
            ->section('event', 'Event', [
                'name',
                'model',
                'res_id',
                'action',
                'user_id',
                'company_id',
                'ip_address',
                'user_agent',
                'created_at',
            ])
            ->section('changes', 'Changes', [
                Field::make('old_values')->code('json'),
                Field::make('new_values')->code('json'),
            ]),
    );
