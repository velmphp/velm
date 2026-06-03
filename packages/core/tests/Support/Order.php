<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;

class Order extends Model
{
    protected static ?string $name = 'test.order';

    protected static ?string $table = 'test_order';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'line_ids' => One2manyField::make('test.order.line', 'order_id')->label('Lines'),
        ];
    }
}
