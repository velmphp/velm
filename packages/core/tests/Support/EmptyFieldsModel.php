<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Models\Model;

final class EmptyFieldsModel extends Model
{
    protected static ?string $name = 'test.empty';

    protected static ?string $table = 'test_empty';

    public static function defineFields(): array
    {
        return [];
    }
}
