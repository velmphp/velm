<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

class OrderLine extends Model
{
    protected static ?string $name = 'test.order.line';

    protected static ?string $table = 'test_order_line';

    public static function defineFields(): array
    {
        return [
            'order_id' => Many2oneField::make('test.order')->label('Order'),
            'description' => CharField::make()->required(),
        ];
    }
}
