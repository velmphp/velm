<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Models\Model;

final class SecurityUser extends Model
{
    protected static ?string $name = 'res.users';

    protected static ?string $table = 'res_users';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'login' => CharField::make()->required()->label('Login'),
            'group_ids' => Many2manyField::make('res.groups'),
        ];
    }
}
