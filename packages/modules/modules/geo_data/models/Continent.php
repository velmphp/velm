<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

class Continent extends Model
{
    protected static ?string $name = 'res.continent';

    protected static ?string $table = 'res_continent';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'code' => CharField::make()->required()->maxLength(2)->label('Code'),
        ];
    }
}
