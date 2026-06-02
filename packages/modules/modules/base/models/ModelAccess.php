<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class ModelAccess extends Model
{
    protected static ?string $name = 'ir.model.access';

    protected static ?string $table = 'ir_model_access';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'model' => CharField::make()->required()->label('Model'),
            'group_id' => Many2oneField::make('res.groups')->label('Group'),
            'perm_read' => BooleanField::make()->default(false)->label('Read'),
            'perm_write' => BooleanField::make()->default(false)->label('Write'),
            'perm_create' => BooleanField::make()->default(false)->label('Create'),
            'perm_unlink' => BooleanField::make()->default(false)->label('Delete'),
        ];
    }
}
