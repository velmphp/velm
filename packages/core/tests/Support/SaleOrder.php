<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class SaleOrder extends Model
{
    protected static ?string $name = 'sale.order';

    protected static ?string $table = 'sale_order';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'state' => CharField::make()->default('draft'),
            'amount' => IntegerField::make()->default(0),
            'country_id' => Many2oneField::make()->comodel('res.country'),
        ];
    }
}
