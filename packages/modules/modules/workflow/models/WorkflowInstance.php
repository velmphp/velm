<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\DatetimeField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class WorkflowInstance extends Model
{
    protected static ?string $name = 'workflow.instance';

    protected static ?string $table = 'workflow_instance';

    public static function defineFields(): array
    {
        return [
            'definition_id' => Many2oneField::make('workflow.definition')->required()->label('Definition'),
            'res_model' => CharField::make()->required()->label('Model'),
            'res_id' => IntegerField::make()->required()->label('Record'),
            'state' => CharField::make()->required()->label('State'),
            'pending_transition' => CharField::make()->label('Pending transition'),
            'stage_data' => TextField::make()->default('{}')->label('Stage data'),
            'started_by' => Many2oneField::make('res.users')->label('Started by'),
            'state_updated_at' => DatetimeField::make()->label('State updated'),
            'active' => BooleanField::make()->default(true)->label('Active'),
        ];
    }
}
