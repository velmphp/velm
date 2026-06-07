<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

class Country extends Model
{
    protected static ?string $name = 'res.country';

    protected static ?string $table = 'res_country';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'code' => CharField::make()->maxLength(2)->label('Code'),
        ];
    }
}
