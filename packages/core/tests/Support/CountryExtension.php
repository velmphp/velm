<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class CountryExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'region_code' => CharField::make()->label('Region code')->maxLength(8),
        ];
    }
}
