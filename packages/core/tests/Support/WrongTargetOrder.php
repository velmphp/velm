<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\One2manyField;
use Velm\Models\Model;

final class WrongTargetOrder extends Model
{
    protected static ?string $name = 'test.wrong.order';

    protected static ?string $table = 'test_wrong_order';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make(),
            'line_ids' => One2manyField::make('test.wrong.line', 'order_id'),
        ];
    }
}
