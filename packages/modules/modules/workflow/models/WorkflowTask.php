<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow\Models;

use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class WorkflowTask extends Model
{
    protected static ?string $name = 'workflow.task';

    protected static ?string $table = 'workflow_task';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'description' => TextField::make()->label('Description'),
            'user_id' => Many2oneField::make('res.users')->label('Assigned to'),
            'date_deadline' => DatetimeField::make()->label('Deadline'),
            'state' => CharField::make()->default('open')->label('State'),
            'priority' => CharField::make()->default('normal')->label('Priority'),
            'res_model' => CharField::make()->label('Linked model'),
            'res_id' => IntegerField::make()->label('Linked record'),
            'instance_id' => Many2oneField::make('workflow.instance')->label('Workflow instance'),
        ];
    }
}
