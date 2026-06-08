<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData\Models;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class CountryExtension extends Model
{
    protected static ?string $name = 'res.country';

    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'continent_id' => Many2oneField::make()->comodel('res.continent')->label('Continent'),
            'iso3' => CharField::make()->maxLength(3)->label('ISO-3'),
            'phone_code' => CharField::make()->label('Phone code'),
            'currency_id' => Many2oneField::make()->comodel('res.currency')->label('Currency'),
            'capital' => CharField::make()->label('Capital'),
            'population' => IntegerField::make()->label('Population'),
            'flag_emoji' => CharField::make()->label('Flag'),
        ];
    }
}
