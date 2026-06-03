<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\One2manyField;
use Velm\Models\Model;

class BadOrder extends Model
{
    protected static ?string $name = 'test.bad.order';

    protected static ?string $table = 'test_bad_order';

    public static function defineFields(): array
    {
        return [
            'line_ids' => One2manyField::make('test.order.line', 'description'),
        ];
    }
}
