<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Fields\Many2oneField;
use Velm\Models\Model;

final class WrongTargetLine extends Model
{
    protected static ?string $name = 'test.wrong.line';

    protected static ?string $table = 'test_wrong_line';

    public static function defineFields(): array
    {
        return [
            'description' => CharField::make(),
            'order_id' => Many2oneField::make('res.country'),
        ];
    }
}
