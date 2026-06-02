<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class SecurityAccess extends Model
{
    protected static ?string $name = 'ir.model.access';

    protected static ?string $table = 'ir_model_access';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'model' => CharField::make()->required()->label('Model'),
            'group_id' => Many2oneField::make('res.groups'),
            'perm_read' => BooleanField::make()->default(false),
            'perm_write' => BooleanField::make()->default(false),
            'perm_create' => BooleanField::make()->default(false),
            'perm_unlink' => BooleanField::make()->default(false),
        ];
    }
}
