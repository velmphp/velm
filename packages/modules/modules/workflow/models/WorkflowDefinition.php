<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class WorkflowDefinition extends Model
{
    protected static ?string $name = 'workflow.definition';

    protected static ?string $table = 'workflow_definition';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'description' => TextField::make()->label('Description'),
            'model' => CharField::make()->required()->label('Model'),
            'definition' => TextField::make()->required()->label('Definition (JSON)'),
            'active' => BooleanField::make()->default(true)->label('Active'),
            'group_ids' => Many2manyField::make('res.groups')->label('Allowed groups'),
        ];
    }
}
