<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow\Models;

use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class WorkflowApproval extends Model
{
    protected static ?string $name = 'workflow.approval';

    protected static ?string $table = 'workflow_approval';

    public static function defineFields(): array
    {
        return [
            'instance_id' => Many2oneField::make('workflow.instance')->required()->label('Instance'),
            'transition_key' => CharField::make()->required()->label('Transition'),
            'status' => CharField::make()->required()->default('pending')->label('Status'),
            'requester_id' => Many2oneField::make('res.users')->label('Requester'),
            'assignee_user_id' => Many2oneField::make('res.users')->label('Assignee user'),
            'assignee_group_id' => Many2oneField::make('res.groups')->label('Assignee group'),
            'acted_by' => Many2oneField::make('res.users')->label('Acted by'),
            'acted_at' => DatetimeField::make()->label('Acted at'),
            'comment' => TextField::make()->label('Comment'),
            'sequence' => IntegerField::make()->default(1)->label('Sequence'),
            'form_data' => TextField::make()->default('{}')->label('Form data'),
            'deadline_at' => DatetimeField::make()->label('Deadline'),
        ];
    }
}
