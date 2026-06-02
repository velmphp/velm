<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class Rule extends Model
{
    protected static ?string $name = 'ir.rule';

    protected static ?string $table = 'ir_rule';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'model' => CharField::make()->required()->label('Model'),
            'group_id' => Many2oneField::make('res.groups')->label('Group'),
            'perm_read' => BooleanField::make()->default(true)->label('Read'),
            'perm_write' => BooleanField::make()->default(true)->label('Write'),
            'perm_create' => BooleanField::make()->default(true)->label('Create'),
            'perm_unlink' => BooleanField::make()->default(true)->label('Delete'),
            'domain' => TextField::make()->required()->label('Domain'),
        ];
    }
}
