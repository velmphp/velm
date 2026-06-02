<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class Country extends Model
{
    protected static ?string $name = 'res.country';

    protected static ?string $table = 'res_country';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make(required: true),
            'code' => CharField::make(maxLength: 2),
        ];
    }
}
