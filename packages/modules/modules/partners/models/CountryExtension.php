<?php

declare(strict_types=1);

namespace Velm\Modules\Partners\Models;

use Velm\Fields\One2manyField;
use Velm\Models\Model;

/**
 * Reverse relation from res.country to res.partner (PyVelm partners parity).
 */
class CountryExtension extends Model
{
    protected static ?string $name = 'res.country';

    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'partner_ids' => One2manyField::make('res.partner', 'country_id')->label('Partners'),
        ];
    }
}
