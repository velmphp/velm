<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\Many2manyField;
use Velm\Models\Model;

final class SecurityGroup extends Model
{
    protected static ?string $name = 'res.groups';

    protected static ?string $table = 'res_groups';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'user_ids' => Many2manyField::make('res.users'),
        ];
    }
}
