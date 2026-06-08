<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\CharField;
use Velm\Fields\FloatField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class CurrencyRate extends Model
{
    protected static ?string $name = 'res.currency.rate';

    protected static ?string $table = 'res_currency_rate';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Date'),
            'rate' => FloatField::make()->required()->label('Rate'),
            'currency_id' => Many2oneField::make()->comodel('res.currency')->required()->label('Currency'),
            'company_id' => Many2oneField::make()->comodel('res.company')->label('Company'),
        ];
    }
}
