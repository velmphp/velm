<?php

declare(strict_types=1);

namespace Velm\Modules\GeoData\Models;

use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class CountryState extends Model
{
    protected static ?string $name = 'res.country.state';

    protected static ?string $table = 'res_country_state';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'code' => CharField::make()->required()->label('ISO 3166-2'),
            'short_code' => CharField::make()->label('Short code'),
            'type' => CharField::make()->label('Type'),
            'country_id' => Many2oneField::make()->comodel('res.country')->label('Country'),
            'parent_id' => Many2oneField::make()->comodel('res.country.state')->label('Parent'),
        ];
    }
}
