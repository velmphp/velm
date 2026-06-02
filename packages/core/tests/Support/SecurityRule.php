<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Fields\TextField;
use Velm\Models\Model;

final class SecurityRule extends Model
{
    protected static ?string $name = 'ir.rule';

    protected static ?string $table = 'ir_rule';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'model' => CharField::make()->required()->label('Model'),
            'group_id' => Many2oneField::make('res.groups'),
            'perm_read' => BooleanField::make()->default(true),
            'perm_write' => BooleanField::make()->default(true),
            'perm_create' => BooleanField::make()->default(true),
            'perm_unlink' => BooleanField::make()->default(true),
            'domain' => TextField::make()->required()->label('Domain'),
        ];
    }
}
