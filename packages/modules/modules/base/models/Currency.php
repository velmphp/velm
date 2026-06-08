<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;

class Currency extends Model
{
    protected static ?string $name = 'res.currency';

    protected static ?string $table = 'res_currency';

    /**
     * @return list<array{0: string, 1: string, 2: mixed}>
     */
    public static function relationalSearchDomain(): array
    {
        return [['active', '=', true]];
    }

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->maxLength(3)->label('Code'),
            'full_name' => CharField::make()->required()->label('Name'),
            'symbol' => CharField::make()->required()->label('Symbol'),
            'decimal_places' => IntegerField::make()->default(2)->label('Decimal places'),
            'active' => BooleanField::make()->default(false)->label('Active'),
            'rate_ids' => One2manyField::make('res.currency.rate', 'currency_id')
                ->listView('currency.rate.list')
                ->label('Rates'),
        ];
    }
}
