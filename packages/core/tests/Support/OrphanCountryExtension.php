<?php

declare(strict_types=1);

namespace Velm\Core\Tests\Support;

use Velm\Fields\CharField;
use Velm\Models\Model;

class OrphanCountryExtension extends Model
{
    protected static ?string $inherit = 'res.country';

    public static function defineFields(): array
    {
        return [
            'orphan' => CharField::make(),
        ];
    }
}
