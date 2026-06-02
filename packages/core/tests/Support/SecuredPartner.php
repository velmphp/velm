<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Models\Model;

final class SecuredPartner extends Model
{
    protected static ?string $name = 'res.partner';

    protected static ?string $table = 'res_partner';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'active' => BooleanField::make()->default(true)->label('Active'),
            'owner_id' => IntegerField::make()->label('Owner'),
        ];
    }
}
