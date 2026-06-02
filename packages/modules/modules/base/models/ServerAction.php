<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\CharField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class ServerAction extends Model
{
    protected static ?string $name = 'ir.actions.server';

    protected static ?string $table = 'ir_actions_server';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'model' => CharField::make()->required()->label('Model'),
            'action_type' => CharField::make()->required()->label('Type'),
            'vals_json' => TextField::make()->label('Values (JSON)'),
        ];
    }
}
