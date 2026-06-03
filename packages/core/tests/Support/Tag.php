<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

class Tag extends Model
{
    protected static ?string $name = 'test.tag';

    protected static ?string $table = 'test_tag';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
        ];
    }
}
