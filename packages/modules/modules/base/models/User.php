<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class User extends Model
{
    protected static ?string $name = 'res.users';

    protected static ?string $table = 'res_users';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'login' => CharField::make()->required()->label('Login'),
            'password' => CharField::make()->label('Password'),
            'active' => BooleanField::make()->default(true)->label('Active'),
            'group_ids' => Many2manyField::make('res.groups')->label('Groups'),
            'company_id' => Many2oneField::make('res.company')->label('Company'),
        ];
    }
}
