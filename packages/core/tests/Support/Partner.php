<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class Partner extends Model
{
    protected static ?string $name = 'res.partner';

    protected static ?string $table = 'res_partner';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make(required: true),
            'active' => BooleanField::make(default: true),
            'country_id' => Many2oneField::make('res.country'),
        ];
    }
}
