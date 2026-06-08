<?php

declare(strict_types=1);

use Velm\Views\Authoring\Card;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\KanbanView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('workflow_instance.list')
            ->model('workflow.instance')
            ->title('Workflow instances')
            ->columns([
                'definition_id',
                'res_model',
                'res_id',
                'state',
                'pending_transition',
                'started_by',
            ]),
        KanbanView::make('workflow_instance.kanban')
            ->model('workflow.instance')
            ->title('Workflow instances')
            ->card(
                Card::make()
                    ->title('res_model')
                    ->subtitle('state')
                    ->fields(['definition_id', 'res_id', 'pending_transition'])
            )
            ->listView('workflow_instance.list'),
        FormView::make('workflow_instance.form')
            ->model('workflow.instance')
            ->section('main', 'Instance', [
                'definition_id',
                'res_model',
                'res_id',
                'state',
                'pending_transition',
                'started_by',
                'state_updated_at',
            ]),
        ListView::make('workflow_approval.list')
            ->model('workflow.approval')
            ->title('Approval requests')
            ->columns([
                'instance_id',
                'transition_key',
                'status',
                'requester_id',
                'assignee_user_id',
                'assignee_group_id',
                'acted_by',
            ]),
        KanbanView::make('workflow_approval.kanban')
            ->model('workflow.approval')
            ->title('Approval requests')
            ->card(
                Card::make()
                    ->title('transition_key')
                    ->subtitle('instance_id')
                    ->fields(['requester_id', 'assignee_user_id'])
            )
            ->listView('workflow_approval.list'),
        FormView::make('workflow_approval.form')
            ->model('workflow.approval')
            ->section('main', 'Approval', [
                'instance_id',
                'transition_key',
                'status',
                'requester_id',
                'assignee_user_id',
                'assignee_group_id',
                'acted_by',
                'acted_at',
                'comment',
            ]),
        ListView::make('workflow_task.list')
            ->model('workflow.task')
            ->title('Tasks')
            ->columns([
                'name',
                'user_id',
                'state',
                'priority',
                'date_deadline',
                'res_model',
                'res_id',
            ]),
        KanbanView::make('workflow_task.kanban')
            ->model('workflow.task')
            ->title('Tasks')
            ->card(
                Card::make()
                    ->title('name')
                    ->subtitle('user_id')
                    ->fields(['priority', 'date_deadline'])
            )
            ->formView('workflow_task.form')
            ->listView('workflow_task.list'),
        FormView::make('workflow_task.form')
            ->model('workflow.task')
            ->section('main', 'Task', [
                'name',
                'description',
                'user_id',
                'state',
                'priority',
                'date_deadline',
                'res_model',
                'res_id',
                'instance_id',
            ]),
    );
