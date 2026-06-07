<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class City extends Model
{
    protected static ?string $name = 'res.city';

    protected static ?string $table = 'res_city';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'country_id' => Many2oneField::make()->comodel('res.country')->label('Country'),
            'state_id' => Many2oneField::make()->comodel('res.country.state')->label('State'),
            'population' => IntegerField::make()->label('Population'),
            'timezone' => CharField::make()->label('Timezone'),
            'geoname_id' => IntegerField::make()->label('GeoNames ID'),
            'is_capital' => BooleanField::make()->default(false)->label('Capital'),
        ];
    }
}
